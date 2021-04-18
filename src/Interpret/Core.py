import io
import re
import sys
from xml.dom import minidom

from src.Interpret.Parser import Parser
from src.Support.ErrorHandler import ErrorHandler
from src.Interpret.Instruction import Instruction, Argument
from src.Interpret.Storage import Storage, Variable


class Interpret:
    handler = ErrorHandler()

    def __init__(self, sourceFile, inputFile):
        """
        Initializes the interpret

        :param sourceFile:  XML source file of IPPcode21
        :param inputFile:   Input file with defined inputs
        """
        # Get Nodes
        tree = self.__getNodes(sourceFile)

        # Run parser
        Parser(tree)

        # Initialize storage
        self.storage = Storage()

        # Initialize instructions
        self.instructions, self.ordersList = self.__collectInstructions(tree)

        # Initialize inputs
        self.inputs = self.__getInputs(inputFile)
        self.inputsFlag = True if self.inputs is not None else False

        # Program counter
        self.counter = 0

        # End if no instructions provided.
        if len(self.instructions) == 0:
            self.handler.terminateProgram(0, 'Interpretation done (no instructions set).')

        # Execute the code
        self.order = self.ordersList[0]
        self.execute(self.__getInstructionAt(self.order))

    def execute(self, instruction: Instruction):
        """
        Executes an instruction.

        :param instruction: Instruction to be executed.
        """
        # Increment counter
        self.counter += 1
        # Set instruction to error handler
        self.handler.instruction = instruction

        if instruction.opcode == 'MOVE':  # MOVE <var> <symb>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb = self.__checkVariable(instruction.getArg(1))

            if symb.value is None:
                self.handler.terminateInterpret(56, 'Uninitialized variable <symb>.')
            self.storage.frames.updateVar(var, symb.value, symb.type)
        elif instruction.opcode == 'CREATEFRAME':  # CREATEFRAME
            self.storage.frames.create('temp')
        elif instruction.opcode == 'PUSHFRAME':  # PUSHFRAME
            self.storage.frames.create('local')
        elif instruction.opcode == 'POPFRAME':  # POPFRAME
            self.storage.frames.pop()
        elif instruction.opcode == 'DEFVAR':  # DEFVAR <var>
            var = instruction.getArg(0)
            self.storage.frames.registerVar(var)
        elif instruction.opcode == 'CALL':  # LABEL <label>
            label = instruction.getArg(0)
            self.storage.calls.push(instruction)
            self.order = self.storage.labels.getOrder(label.value)
            self.execute(self.__getInstructionAt(self.order))
        elif instruction.opcode == 'RETURN':  # RETURN
            order = self.storage.calls.pop().order
            self.execute(self.__getInstructionAt(order, True))
        elif instruction.opcode == 'PUSHS':  # PUSHS <symb>
            symb = self.__checkVariable(instruction.getArg(0))
            self.storage.stack.push({'value': symb.value, 'type': symb.type})
        elif instruction.opcode == 'POPS':  # POPS <var>
            var = self.__checkVariable(instruction.getArg(0), False)
            item = self.storage.stack.pop()
            self.storage.frames.updateVar(var, item.get('value'), item.get('type'))
        elif instruction.opcode == 'ADD':  # ADD <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeArithmeticOperation(instruction)
            self.storage.frames.updateVar(var, int(symb1.value) + int(symb2.value), 'int')
        elif instruction.opcode == 'SUB':  # SUB <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeArithmeticOperation(instruction)
            self.storage.frames.updateVar(var, int(symb1.value) - int(symb2.value), 'int')
        elif instruction.opcode == 'MUL':  # MUL <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeArithmeticOperation(instruction)
            self.storage.frames.updateVar(var, int(symb1.value) * int(symb2.value), 'int')
        elif instruction.opcode == 'IDIV':  # IDIV <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeArithmeticOperation(instruction)
            if int(symb2.value) == 0:
                self.handler.terminateProgram(57, 'Division by zero.')
            self.storage.frames.updateVar(var, int(symb1.value) / int(symb2.value), 'int')
        elif instruction.opcode == 'LT':  # LT <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeRelationOperation(instruction)
            value = 'true' if symb1.value < symb2.value else 'false'
            self.storage.frames.updateVar(var, value, 'bool')
        elif instruction.opcode == 'GT':  # GT <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeRelationOperation(instruction)
            value = 'true' if symb1.value > symb2.value else 'false'
            self.storage.frames.updateVar(var, value, 'bool')
        elif instruction.opcode == 'EQ':  # EQ <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeRelationOperation(instruction)
            value = 'true' if symb1.value == symb2.value else 'false'
            self.storage.frames.updateVar(var, value, 'bool')
        elif instruction.opcode == 'AND':  # AND <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeBooleanOperation(instruction)
            value = 'true' if symb1 & symb2 else 'false'
            self.storage.frames.updateVar(var, value, 'bool')
        elif instruction.opcode == 'OR':  # OR <var> <symb1> <symb2>
            var, symb1, symb2 = self.__initializeBooleanOperation(instruction)
            value = 'true' if symb1 | symb2 else 'false'
            self.storage.frames.updateVar(var, value, 'bool')
        elif instruction.opcode == 'NOT':  # NOT <var> <symb>
            var, symb = self.__initializeBooleanOperation(instruction)
            value = 'false' if symb else 'true'
            self.storage.frames.updateVar(var, value, 'bool')
        elif instruction.opcode == 'INT2CHAR':  # INT2CHAR <var> <symb>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb = self.__checkVariable(instruction.getArg(1))
            if not symb.isInt():
                self.handler.terminateInterpret(53, 'Int is expected as second parameter.')
            try:
                self.storage.frames.updateVar(var, chr(int(symb.value)), 'string')
            except ValueError:
                self.handler.terminateInterpret(58, 'Value of second parameter is out of range.')
        elif instruction.opcode == 'STRI2INT':  # STRI2INT <var> <symb1> <symb2>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb1 = self.__checkVariable(instruction.getArg(1))
            symb2 = self.__checkVariable(instruction.getArg(2))
            if not symb1.isString() or not symb2.isInt():
                self.handler.terminateInterpret(53, 'Params error (<got:expected>) '
                                                    '<' + symb1.type + ':string> <' + symb2.type + ':int>')
            index = int(symb2.value)
            if index >= len(symb1.value) or index < 0:
                self.handler.terminateInterpret(58, 'Index is out of range.')
            self.storage.frames.updateVar(var, int(ord(symb1.value[index])), 'int')
        elif instruction.opcode == 'READ':  # READ <var> <type>
            var = self.__checkVariable(instruction.getArg(0), False)
            readType = instruction.getArg(1).value

            if self.inputsFlag:
                if len(self.inputs) > 0:
                    read = self.inputs.pop(0)
                else:
                    read = ''
            else:
                read = input()

            if len(read) == 0:
                self.storage.frames.updateVar(var, 'nil', 'nil')
            elif readType == 'int':
                read = read.lstrip().rstrip()
                if read.lstrip('-').isdigit():
                    self.storage.frames.updateVar(var, int(read), 'int')
                else:
                    self.storage.frames.updateVar(var, 'nil', 'nil')
            elif readType == 'string':
                read = read.lstrip().rstrip()
                self.storage.frames.updateVar(var, str(read), 'string')
            elif readType == 'bool':
                read = read.lstrip().rstrip()
                if read.lower() == 'true':
                    self.storage.frames.updateVar(var, 'true', 'bool')
                else:
                    self.storage.frames.updateVar(var, 'false', 'bool')
        elif instruction.opcode == 'WRITE':  # WRITE <symb>
            symb = self.__checkVariable(instruction.getArg(0))
            if symb.isNil():
                print()
            elif symb.isBool():
                print(symb.value)
            elif symb.isInt():
                print(int(symb.value), end='')
            else:
                symb.value = str(symb.value)
                escapes = re.findall(r'\\[0-9]{3}', symb.value)
                for escape in escapes:
                    symb.value = symb.value.replace(escape, chr(int(escape.lstrip('\\').rstrip())))
                print(symb.value, end='')
        elif instruction.opcode == 'CONCAT':  # CONCAT <var> <symb1> <symb2>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb1 = self.__checkVariable(instruction.getArg(1))
            symb2 = self.__checkVariable(instruction.getArg(2))
            if not symb1.isString() or not symb2.isString():
                self.handler.terminateInterpret(53, 'Can concatenate only strings.')
            self.storage.frames.updateVar(var, str(symb1.value) + str(symb2.value), 'string')
        elif instruction.opcode == 'STRLEN':  # STRLEN <var> <symb>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb = self.__checkVariable(instruction.getArg(1))
            if not symb.isString():
                self.handler.terminateInterpret(53, 'Second parameter is not a string.')
            self.storage.frames.updateVar(var, len(symb.value), 'int')
        elif instruction.opcode == 'GETCHAR':  # GETCHAR <var> <symb1> <symb2>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb1 = self.__checkVariable(instruction.getArg(1))
            symb2 = self.__checkVariable(instruction.getArg(2))
            if not symb1.isString() or not symb2.isInt():
                self.handler.terminateInterpret(53, 'Params error (<got:expected>) '
                                                    '<' + symb1.type + ':string> <' + symb2.type + ':int>')
            index = int(symb2.value)
            if index >= len(symb1.value) or index < 0:
                self.handler.terminateInterpret(58, 'Index is out of range.')
            self.storage.frames.updateVar(var, symb1.value[index], 'string')
        elif instruction.opcode == 'SETCHAR':  # SETCHAR <var> <symb1> <symb2>
            var = self.__checkVariable(instruction.getArg(0))
            symb1 = self.__checkVariable(instruction.getArg(1))
            symb2 = self.__checkVariable(instruction.getArg(2))
            if not var.isString() or not symb1.isInt() or not symb2.isString():
                self.handler.terminateInterpret(53, 'Params error (<got:expected>) '
                                                    '<' + var.type + ':string> '
                                                    '<' + symb1.type + ':string> <' + symb2.type + ':int>')

            index = int(symb1.value)
            if index >= len(var.value) or index < 0 or not len(symb2.value):
                self.handler.terminateInterpret(58, 'Index is out of range or third parameter is empty.')
            var.value = var.value[0:index] + symb2.value[0] + var.value[index+1:]
            self.storage.frames.updateVar(var, var.value, 'string')
        elif instruction.opcode == 'TYPE':  # TYPE <var> <symb>
            var = self.__checkVariable(instruction.getArg(0), False)
            symb = self.__checkVariable(instruction.getArg(1), False)
            if symb.isInitialized():
                self.storage.frames.updateVar(var, symb.type, 'string')
            else:
                self.storage.frames.updateVar(var, '', 'string')
        elif instruction.opcode == 'LABEL':  # LABEL <label>
            pass
        elif instruction.opcode == 'JUMP':  # JUMP <label>
            label = instruction.getArg(0)
            self.order = self.storage.labels.getOrder(label.value)
            self.execute(self.__getInstructionAt(self.order))
        elif (instruction.opcode == 'JUMPIFEQ' or
              instruction.opcode == 'JUMPIFNEQ'):  # JUMPIF(N)EQ <label> <symb1> <symb2>
            label = instruction.getArg(0)
            symb1 = self.__checkVariable(instruction.getArg(1))
            symb2 = self.__checkVariable(instruction.getArg(2))

            if not self.storage.labels.has(label.value):
                self.handler.terminateInterpret(52, 'Label does not exists.')

            if not symb1.isNil() and not symb2.isNil() and symb1.type != symb2.type:
                self.handler.terminateInterpret(53, "Types does not match or symbols are not 'nil'.")
            if ((instruction.opcode == 'JUMPIFEQ' and symb1.value == symb2.value) or
                    (instruction.opcode == 'JUMPIFNEQ' and symb1.value != symb2.value)):
                order = self.storage.labels.getOrder(label.value)
                self.execute(self.__getInstructionAt(order, True))
        elif instruction.opcode == 'EXIT':  # EXIT <symb>
            symb = self.__checkVariable(instruction.getArg(0))
            if not symb.isInt():
                self.handler.terminateInterpret(53, 'Excepted int.')
            symb.value = int(symb.value)
            if not (0 <= symb.value <= 49):
                self.handler.terminateInterpret(57, 'Invalid exit code value (excepted range: 0-49).')
            self.handler.terminateProgram(symb.value, 'Terminated by EXIT instruction.')
        elif instruction.opcode == 'DPRINT':  # DPRINT <symb>
            symb = self.__checkVariable(instruction.getArg(0))
            print(symb.value, file=sys.stderr)
        elif instruction.opcode == 'BREAK':  # BREAK
            stats = "Executions: " + str(self.counter) + '\n' \
                    "Current order: " + str(self.order + 1) + '\n' \
                    "========== Storage ==========\n" + str(self.storage.statement()) + '' \
                    "============================="
            print(stats, file=sys.stderr)

        # Execute next instruction
        self.execute(self.__getInstructionAt(self.order, True))

    def __getNodes(self, source):
        """
        Gets nodes from XML source file.

        :param source: XML source file
        :return: XML Object that refers to the root of XML file.
        """
        try:
            tree = minidom.parse(source)

            return tree
        except Exception as exception:
            self.handler.terminateProgram(31, 'XML Error: ' + str(exception))

    @staticmethod
    def __getInputs(source) -> list or None:
        inputs = None

        if not source:
            return inputs

        inputs = list()

        if isinstance(source, io.TextIOWrapper):
            for line in source:
                inputs.append(line)
            return inputs

        file = open(source)
        for line in file:
            inputs.append(line)
        return inputs

    def __collectInstructions(self, tree) -> tuple:
        """
        Initializes instructions into a collection.

        :param tree: XML tree from source file
        :return: List of instructions sorted by order.
        """
        collection = list()

        instructions = tree.getElementsByTagName('instruction')

        for instruction in instructions:
            arguments = instruction.childNodes
            collection.append(self.__registerInstruction(instruction, arguments))

        # Sort by order
        collection.sort(key=lambda x: x.order)

        # Check for duplicated orders and create orders list
        ordersList = list()
        for instruction in collection:
            if instruction.order in ordersList:
                self.handler.terminateProgram(32, 'Order duplication.')
            ordersList.append(instruction.order)

        return collection, ordersList

    def __registerInstruction(self, instructionNode, argNodes) -> Instruction:
        """
        Register an instruction.

        :param instructionNode: <instruction> node
        :param argNodes:        <argX> node
        :return:                Instruction instance
        """
        instruction = Instruction()

        instruction.setOrder(instructionNode.getAttribute('order'))
        instruction.setOpcode(instructionNode.getAttribute('opcode').upper())

        # Sort arguments
        argNodes = [x for x in argNodes if x.nodeType == 1]
        argNodes.sort(key=lambda x: x.tagName)

        # Set arguments
        for argument in argNodes:
            if argument.nodeType == 1:  # Get only elements
                instruction.setArg(
                    argument.getAttribute('type'),
                    argument.childNodes[0].nodeValue if argument.hasChildNodes() else ''
                )

        # Register labels
        if instruction.isLabel():
            if self.storage.labels.has(instruction):
                self.handler.terminateProgram(52, 'This label is already set.')
            self.storage.labels.register(instruction.getArg(0).value, instruction.order)

        return instruction

    def __getInstructionAt(self, order, nextInstruction=False) -> Instruction:
        """
        Gets an instruction at orderList's index.

        :param order: The order of instruction
        :return: Instance of instruction
        """
        index = None
        for i in range(len(self.ordersList)):
            if order == self.ordersList[i]:
                index = i
                break

        # Instruction not found
        if index is None:
            self.handler.terminateProgram(99, 'Instruction at order '+str(index)+' not found.')

        # If returned find next order
        if nextInstruction:
            index += 1

        # If no next index that means we are done.
        if index >= len(self.instructions):
            self.handler.terminateProgram(0, 'Interpret done.')

        self.order = self.instructions[index].order

        return self.instructions[index]

    def __checkVariable(self, var: Argument, initRequired=True) -> Argument or Variable:
        """
        Checks a variable.

        :param var: Variable that is checked
        :return: If it is a variable return variable from storage otherwise return it back.
        """
        if var.isVar() and not self.storage.frames.has(var.frame):
            self.handler.terminateInterpret(55, 'Frame ' + var.frame + ' is not set')
        if var.isVar() and not self.storage.frames.hasVar(var):
            self.handler.terminateInterpret(54, 'Undefined variable <' + var.value + ':' + var.type + '>')
        if var.isVar():
            variable = self.storage.frames.getVar(var)
            if initRequired and not variable.isInitialized():
                self.handler.terminateInterpret(56, "Variable '" + var.value + "' is not initialized")
            return variable
        if var.isInt():
            var.value = int(var.value)
            return var
        if var.isString():
            # If string parse regex
            var.value = self.__stringEscapesCheck(var.value)
        return var

    @staticmethod
    def __stringEscapesCheck(string):
        """
        Checks string for escape characters.

        :param string: The checked string
        :return: String with removed escape sequences.
        """
        string = str(string)
        escapes = re.findall(r'\\[0-9]{3}', string)
        for escape in escapes:
            string = string.replace(escape, chr(int(escape.lstrip('\\'))))
        return string

    def __initializeArithmeticOperation(self, instruction: Instruction) -> tuple:
        """
        Initialize arguments for arithmetic operation.

        :param instruction: Arithmetic instruction
        :return: Initialized operands for arithmetic operations.
        """
        var = self.__checkVariable(instruction.getArg(0), False)
        symb1 = self.__checkVariable(instruction.getArg(1))
        symb2 = self.__checkVariable(instruction.getArg(2))
        if not symb1.isInt() or not symb2.isInt():
            self.handler.terminateProgram(53, 'Int expected')
        return var, symb1, symb2

    def __initializeRelationOperation(self, instruction: Instruction) -> tuple:
        """
        Initialize arguments for relation operation.

        :param instruction: Relation instruction
        :return: Initialized operands for relation operations.
        """
        var = self.__checkVariable(instruction.getArg(0), False)
        symb1 = self.__checkVariable(instruction.getArg(1))
        symb2 = self.__checkVariable(instruction.getArg(2))
        if symb1.type != symb2.type or (symb1.isNil() or symb2.isNil()):
            self.handler.terminateInterpret(53, 'Types of operands do not match ' + symb1.type + ' != ' + symb2.type)
        if not symb1.isRelationValid() or not symb2.isRelationValid():
            self.handler.terminateInterpret(53, 'Not valid types <' + symb1.type + ':symb1> <' + symb2.type + ':symb2>')
        return var, symb1, symb2

    def __initializeBooleanOperation(self, instruction: Instruction) -> tuple:
        """
        Initialize arguments for boolean operation.

        :param instruction: Boolean instruction
        :return: Initialized operands for boolean operations.
        """
        var = self.__checkVariable(instruction.getArg(0), False)
        symb1 = self.__checkVariable(instruction.getArg(1))
        symb2 = self.__checkVariable(instruction.getArg(2)) if len(instruction.args) > 2 else None
        if not symb1.isBool() or (symb2 and not symb2.isBool()):
            self.handler.terminateProgram(53, 'Expected Boolean.')
        if symb2:
            symb1 = True if symb1.value == 'true' else False
            symb2 = True if symb2.value == 'true' else False
            return var, symb1, symb2
        else:
            symb1 = True if symb1.value == 'true' else False
            return var, symb1
