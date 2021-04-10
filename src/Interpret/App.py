from src.Interpret.Argument import Argument
from src.Interpret.Core import Interpret
from src.Support.ErrorHandler import ErrorHandler


class App:
    handler = ErrorHandler()

    def __init__(self):
        """
        Initialize error handler and argument handler.
        """
        self.programName = ""
        self.Argument = Argument()

    def registerArguments(self, arguments: list):
        """
        Adds arguments into registry.

        :param arguments: Arguments passed into program
        """
        for argument in arguments:
            self.Argument.register(argument)

    def listen(self, arguments: list):
        """
        Listen for arguments.

        :param arguments: The entered arguments
        """
        self.programName = arguments.pop(0)

        self.parseArguments(arguments)

    def runInterpret(self):
        """
        Runs the interpret
        """
        if self.Argument.isSet('source'):
            sourceFile = self.Argument.getPath('source')
            if not self.Argument.isValidPath(sourceFile):
                self.handler.terminateProgram(11, 'File ' + sourceFile + ' is invalid.')
        else:
            sourceFile = input()

        if self.Argument.isSet('input'):
            inputFile = self.Argument.getPath('input')
            if not self.Argument.isValidPath(inputFile):
                self.handler.terminateProgram(11, 'File ' + inputFile + ' is invalid.')
        else:
            inputFile = input()

        Interpret(sourceFile, inputFile)

    def parseArguments(self, arguments: list):
        """
        Parse entered arguments.

        :param arguments: Arguments to parse
        """
        # Check if at least one argument is set
        if len(arguments) == 0:
            self.handler.terminateProgram(10, "At least one argument is required.")

        # Loop through arguments
        for argument in arguments:
            # Check if its valid argument
            if not self.Argument.isValid(argument):
                self.handler.terminateProgram(10, "Argument: " + argument + " is invalid.")

            # Check if argument is --help
            if self.Argument.isHelp(argument):
                if len(arguments) > 1:
                    self.handler.terminateProgram(10, "Can not use more arguments with --help argument.")
                self.printHelp()

            self.Argument.add(argument)

    def printHelp(self):
        """
        Prints help
        """
        print("Interpret for XML representation of IPPcode21")
        print("Usage: py " + self.programName + " [--help] OPTIONS")
        print("Arguments:")
        print("\t--help\tDisplay this help and exit.")
        print("OPTIONS:")
        print("\t--source=file\tInput file with XML representation of IPPcode21.")
        print("\t--input=file\tFile with inputs for the interpretation of the entered source code.")
        self.handler.terminateProgram(0)

    def terminate(self):
        """
        Terminate application
        """
        self.handler.terminateProgram(0, 'Interpretation is done.')
