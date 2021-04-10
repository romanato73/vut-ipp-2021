from os import path
from src.Support.ErrorHandler import ErrorHandler


class Argument:
    handler = ErrorHandler()

    def __init__(self):
        """
        Initialize allowed arguments and arguments passed by program.
        """
        self.allowedArguments = ["--help"]
        self.arguments = []

    def register(self, argument: str):
        """
        Add argument into registry of allowed arguments.

        :param argument: The entered argument
        """
        self.allowedArguments.append(argument)

    def add(self, argument: str):
        """
        Add argument into registry.

        :param argument: The entered argument
        """
        self.arguments.append(argument)

    def isValid(self, argument: str) -> bool:
        """
        Checks if argument is valid.

        :param argument: The entered argument
        :return: True if it is a valid argument otherwise false.
        """
        for allowedArgument in self.allowedArguments:
            if self.getReal(argument) == self.getReal(allowedArgument):
                return True
        return False

    def isHelp(self, argument: str) -> bool:
        """
        Checks if argument is --help argument.

        :param argument: The entered argument
        :return: True if argument is help otherwise false.
        """
        if self.getReal(argument) == "help":
            return True
        return False

    def isSet(self, name: str) -> bool:
        """
        Checks if argument is set.

        :param name: Name of argument
        :return: True if set otherwise false.
        """
        for argument in self.arguments:
            if self.getReal(argument) == name:
                return True
        return False

    @staticmethod
    def getReal(argument: str):
        """
        Checks if argument is --help argument.

        :param argument: The entered argument
        """
        # Remove dashes
        argument = argument[2:]

        # Find equal sign
        eqPos = argument.find('=')

        if eqPos != -1:
            return argument[:eqPos]
        else:
            return argument

    def getPath(self, name: str) -> str:
        """
        Gets path from argument

        :param name: Name of argument
        :return: Path of argument or Internal Error if fail
        """
        # Found argument
        found = name

        # Find equal sign
        for argument in self.arguments:
            if self.getReal(argument) == name:
                found = argument

        eqPos = found.find('=')

        if eqPos == -1:
            self.handler.terminateProgram(99, 'This argument does not have path.')

        return found[(eqPos+1):]

    @staticmethod
    def isValidPath(file: str) -> bool:
        """
        Checks path if exists

        :param file: The checked path
        :return: True if path exists otherwise false.
        """
        if path.exists(file):
            return True
        return False
