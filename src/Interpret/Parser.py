import re
from src.Support.DataHandler import instructions
from src.Support.ErrorHandler import ErrorHandler


class Parser:
    def __init__(self, tree):
        """
        Parser invoker.

        :param tree: tree of the XML structure.
        """
        self.handler = ErrorHandler()

        root = self.__checkRootNode(tree.documentElement)

        self.__checkNodes(root.childNodes)

    def __checkRootNode(self, root):
        """
        Checks root node element.

        :param root: The root node
        """
        allowedAttributes = ['name', 'description', 'language']

        if root.tagName != 'program':
            self.handler.terminateProgram(32, 'Unknown root element: ' + root.tagName)

        if not self.__hasValidAttributes(root, allowedAttributes):
            self.handler.terminateProgram(32, '<program> has illegal attribute')

        return root

    def __checkNodes(self, nodes):
        """
        Checks if nodes contains only instructions.

        :param nodes: The checked nodes
        """
        for node in nodes:
            if node.nodeType == 1:
                self.__checkInstructionNode(node)
            else:
                if node.nodeType == 3 and node.data.lstrip().rstrip() == "":  # TEXT Node
                    continue
                self.handler.terminateProgram(32, 'Illegal nodeType.')

    def __checkInstructionNode(self, node):
        """
        Checks if node is an instruction.

        :param node: The checked node
        """
        allowedAttributes = ['order', 'opcode']

        if node.tagName != 'instruction':
            self.handler.terminateProgram(32, 'Illegal tag name: ' + node.tagName)
        if not self.__hasValidAttributes(node, allowedAttributes):
            self.handler.terminateProgram(32, '<instruction> has illegal attributes.')

        # Check if instruction exists
        if node.getAttribute('opcode') not in instructions:
            self.handler.terminateProgram(32, 'Unknown instruction.')

        # Check if order > 0
        order = node.getAttribute('order')
        if not order.lstrip('-').isdigit() or int(order) < 1:
            self.handler.terminateProgram(32, 'Order attribute has incorrect value.')

        # Check child (arg) nodes
        if node.hasChildNodes():
            self.__checkArgNodes(node.getAttribute('opcode'), node.childNodes)

    def __checkArgNodes(self, instruction, nodes):
        """
        Checks arg nodes of instruction.

        :param instruction: Instruction that has these args
        :param nodes:       The checked nodes
        """
        index = 0
        for node in nodes:
            if node.nodeType == 1:
                # Check for index
                if index >= len(instructions.get(instruction)):
                    self.handler.terminateProgram(32, 'Instruction has invalid number of operands.')
                # Check argument node
                self.__checkArgNode(node, index+1, instructions.get(instruction)[index], instruction)
                index += 1
            else:
                if node.nodeType == 3 and node.data.lstrip().rstrip() == "":  # TEXT Node newline
                    continue
                self.handler.terminateProgram(32, 'Illegal nodeType.')
        if index != len(instructions.get(instruction)):
            self.handler.terminateProgram(32, 'Instruction has invalid number of operands.')

    def __checkArgNode(self, node, order, operandType, instruction):
        """
        Checks current arg node and its value.

        :param node:        Current arg node
        :param order:       Order of current arg node
        :param operandType: Type of operand
        :param instruction: Instruction that is related to current argument
        """
        allowedAttributes = ['type']
        order = str(order)

        if not re.fullmatch('(arg'+order+')', node.tagName):
            self.handler.terminateProgram(32, instruction + ' has ' + node.tagName + ' but <arg'+order+'> expected')
        if not self.__hasValidAttributes(node, allowedAttributes):
            self.handler.terminateProgram(32, '<arg'+order+'> has illegal attributes')

        # Check child (text) nodes
        if node.hasChildNodes():
            for arg in node.childNodes:
                if arg.nodeType == 3:  # TEXT Nodes
                    if not self.__isValueValid(arg.data, node.getAttribute('type'), operandType):
                        self.handler.terminateProgram(32, "Argument's operand is not valid.")
                else:
                    self.handler.terminateProgram(32, 'Illegal nodeType.')
        else:
            self.handler.terminateProgram(32, instruction + "'s <arg"+order+"> has empty content.")

    @staticmethod
    def __hasValidAttributes(node, attributes: list) -> bool:
        """
        Checks if attribute is valid.

        :param node: The checked node
        :param attributes: List of allowed attributes
        :return: False if attributes are not in allowed attributes otherwise True.
        """
        for i in range(node.attributes.length):
            if node.attributes.item(i).name not in attributes:
                return False
        return True

    @staticmethod
    def __isValueValid(expression: str, argType: str, operandType: str) -> bool:
        """
        Check if arg has valid value

        :param expression:  The checked expression
        :param argType:     Argument type (actual)
        :param operandType: Operand type (from instruction)
        :return:
        """
        frames = ['GF', 'LF', 'TF']

        if operandType == 'var' or (operandType == 'symb' and argType == 'var'):
            if argType != 'var':
                return False
            if expression[0:2] not in frames:
                return False
            if expression[2] != '@':
                return False
            if not re.match('[a-zA-Z?!*%$&_-]', expression[3]):
                return False
            if not re.fullmatch('^([a-zA-Z0-9?!*%$&_-])*$', expression[3:]):
                return False
        elif operandType == 'symb':
            args = ['int', 'bool', 'nil', 'string']
            if argType not in args:
                return False
            if argType == 'int' and not expression.lstrip('-').isdigit():
                return False
            if argType == 'bool' and (expression != 'true' or expression != 'false'):
                return False
            if argType == 'nil' and expression != 'nil':
                return False
            if argType == 'string' and not isinstance(expression, str):
                return False
        elif operandType == 'label':
            if argType != 'label':
                return False
            if not re.match('[a-zA-Z?!*%$&_-]', expression[0]):
                return False
            if not re.fullmatch('^([a-zA-Z0-9?!*%$&_-])*$', expression):
                return False
        elif operandType == 'type':
            if argType != 'type':
                return False
            if expression != 'int' and expression != 'string' and expression != 'bool':
                return False

        return True
