from src.Interpret.Interfaces import ArgumentInterface
from src.Support.ErrorHandler import ErrorHandler


class Instruction:
    handler = ErrorHandler()

    def __init__(self):
        """
        Initialize Instruction
        """
        self.order = None
        self.opcode = None
        self.args = list()

    def setOrder(self, order: str):
        """
        Sets the order of instruction.

        :param order: Order of instruction
        """
        self.order = int(order)

    def setOpcode(self, opcode: str):
        """
        Sets the opcode of instruction.

        :param opcode: Opcode of instruction
        """
        self.opcode = opcode

    def setArg(self, argType: str, value: str):
        """
        Sets the argument of instruction.

        :param argType: Argument type
        :param value:   Argument value
        """
        self.args.append(Argument(argType, value))

    def getArg(self, index: int):
        """
        Gets the argument of instruction.

        :param index: Argument index
        """
        return self.args[index]

    def isLabel(self):
        """
        Checks whether instruction is a LABEL.
        """
        return True if self.opcode == 'LABEL' else False


class Argument(ArgumentInterface):
    handler = ErrorHandler()

    def __init__(self, argType: str, value: str):
        """
        Initializes the argument

        :param argType: Argument type
        :param value:   Argument value
        """
        self.type = argType

        if len(value) > 2 and value[2] == '@':
            self.frame = value[:2]
            self.value = value[3:]
        else:
            self.frame = None
            self.value = value
