from src.Support.DataHandler import errors


class ErrorHandler:
    def __init__(self):
        """
        Error handler initialize
        """
        self.instruction = None
        self.debug = False

    def terminateProgram(self, code: int, message: str = None):
        """
        Terminates the program with error code.

        :param code:    Exit code
        :param message: Exit message
        """
        if str(code) not in errors:
            print(message) if message and self.debug else None
            exit(code)

        if self.debug:
            print(errors[str(code)])
            print(message) if message else None
        exit(code)

    def terminateInterpret(self, code: int, message: str = None):
        """
        Terminates the interpret.

        :param code:    Exit code
        :param message: Exit message
        """
        if str(code) not in errors:
            print(message) if message and self.debug else None
            exit(code)

        if self.debug:
            print(errors[str(code)])
            print(self.instruction.opcode + '@' + str(self.instruction.order), end=': ')
            print(message) if message else print('Unknown error.')
        exit(code)
