from src.Interpret.Instruction import Argument
from src.Interpret.Interfaces import ArgumentInterface, StackInterface
from src.Support.ErrorHandler import ErrorHandler


class Storage:
    handler = ErrorHandler()

    def __init__(self):
        """
        Initializes a storage.
        """
        self.frames = Frames()
        self.stack = Stack()
        self.labels = Labels()
        self.calls = Stack()

    def statement(self):
        """
        Returns the statement of Storage.
        :return: Statement of storage in string.
        """
        string = "Global Frame:\n" + self.frames.get('global').statement() + "\n" \
                 "Temp Frame:\n" + self.frames.get('temp', True).statement() + "\n" \
                 "Local Frame:\n" + self.frames.get('local', True).statement() + "\n" \
                 "Stack:\n" + self.stack.statement() + "\n" \
                 "Call Stack:\n" + self.calls.statement(True) + ""
        return string


class Variable(ArgumentInterface):
    def __init__(self, frame, name, value, varType):
        """
        Initializes a variable.
        :param frame:   Variable frame
        :param name:    Variable name
        :param value:   Variable value
        :param varType: Variable type
        """
        self.handler = ErrorHandler()
        self.frame = frame
        self.name = name
        self.value = value
        self.type = varType

    def setValue(self, value):
        """
        Sets a value of variable.

        :param value: New value that is set to a variable
        """
        self.value = value

    def setType(self, varType):
        """
        Sets a type of variable.

        :param varType: New type that is set to a variable
        """
        self.type = varType

    def isVar(self):
        """
        Fixes isVar() method for variable.
        """
        return True if self.isInt() or self.isBool() or self.isString() or self.isNil() else False


class Variables:
    handler = ErrorHandler()

    def __init__(self):
        """
        Initializes a variable registry.
        """
        self.registry = list()

    def register(self, arg: Argument) -> Variable:
        """
        Register a variable into registry.

        :param arg: Argument that is registered.
        :return: Variable object.
        """
        if self.has(arg.value):
            self.handler.terminateProgram(52, "Variable '" + arg.value + "' already exists.")

        variable = Variable(arg.frame, arg.value, None, None)
        self.registry.append(variable)

        return variable

    def has(self, name: str) -> bool:
        """
        Check if variables has a variable.

        :param name: Name of variable.
        :return: True if variable found otherwise false.
        """
        for item in self.registry:
            if name == item.name:
                return True
        return False

    def get(self, name: str) -> Variable:
        """
        Gets a variable from variables.

        :param name: Name of variable.
        :return: Found variable object.
        """
        for item in self.registry:
            if item.name == name:
                return item
        self.handler.terminateProgram(52, "Variable '" + name + "' is not set")

    def getAll(self) -> list:
        """
        Registry of variables.
        :return: Registry of variables.
        """
        return self.registry

    def update(self, var: Variable, value: str, varType: str):
        """
        Updates a variable.

        :param var:     Variable that is updated
        :param value:   New value of variable
        :param varType: New type of variable
        """
        for index in range(len(self.registry)):
            if self.registry[index].name == var.name:
                self.registry[index].setValue(value)
                self.registry[index].setType(varType)
                return
        self.handler.terminateProgram(52, "Variable '" + var.name + "' is not set")

    def clone(self, variables):
        """
        Clones a variable into this registry.

        :param variables: Variables that are cloned
        """
        self.registry = variables.registry

    def statement(self) -> str:
        """
        Prints a statement of variables.

        :return: Statement of variables as string.
        """
        string = ""
        for item in self.registry:
            string += '<' + str(item.type) + '>' + str(item.name) + '=' + str(item.value) + '\n'
        return string


class Frames:
    handler = ErrorHandler()

    def __init__(self):
        """
        Initializes interpret frames.
        """
        self.__global = Variables()
        self.__temp = None
        self.__locals = list()
        self.__nesting = -1

    def create(self, frameType: str):
        """
        Creates a new frame.

        :param frameType: Frame type
        """
        if frameType == 'local':
            if self.__temp is None:
                self.handler.terminateProgram(55, 'Accessing to non-defined temporary frame.')
            variables = Variables()
            variables.clone(self.__temp)
            self.__locals.append(variables)
            self.__nesting += 1
            for variable in self.get('local').getAll():
                variable.frame = 'LF'
            self.__temp = None
        if frameType == 'temp':
            self.__temp = Variables()

    def get(self, frameType: str, statement=False) -> Variables or None:
        """
        Gets requested frame.

        :param frameType: Frame type
        :param statement: If a statement is called create new instance for local/temp frame if None.
        :return: Requested frame.
        """
        if frameType == 'global' or frameType == 'GF':
            return self.__global
        if frameType == 'temp' or frameType == 'TF':
            if statement and self.__temp is None:
                return Variables()
            return self.__temp
        if frameType == 'local' or frameType == 'LF':
            if statement:
                return Variables()
            if self.__nesting >= 0:
                return self.__locals[self.__nesting]
            return None

    def pop(self):
        """
        Pops local frame into a temporary frame.
        """
        if self.__nesting != -1 and self.__nesting < len(self.__locals):
            self.__temp = self.__locals[self.__nesting]
            self.__nesting -= 1
        else:
            self.handler.terminateProgram(55, 'Accessing to non-existing local frame.')

    def has(self, frameType):
        """
        Checks whether frame exists.

        :param frameType: Type of frame
        :return:
        """
        if frameType == 'GF' or frameType == 'global':
            return True

        frame = self.get(frameType)

        if isinstance(frame, Variables):
            return True
        if isinstance(frame, list):
            return True
        return True

    def registerVar(self, var: Argument) -> Variable:
        """
        Registers a variable into a frame.

        :param var: Variable that holds required parameters
        :return: Registered Variable.
        """
        self.__checkFrame(var.frame)
        return self.get(var.frame).register(var)

    def getVar(self, var: Argument or Variable):
        """
        Gets a variable from a frame.

        :param var: Argument of Variable instance that is searched.
        :return: Variable that is found in storage.
        """
        self.__checkFrame(var.frame)
        if isinstance(var, Argument):
            return self.get(var.frame).get(var.value)
        if isinstance(var, Variable):
            return self.get(var.frame).get(var.name)

    def updateVar(self, var: Variable, value: str, varType: str):
        """
        Updates a variable.

        :param var:     Variable that is updated.
        :param value:   New value for variable.
        :param varType: New type for variable.
        :return:
        """
        self.__checkFrame(var.frame)
        return self.get(var.frame).update(var, value, varType)

    def hasVar(self, var: Argument):
        """
        Check if frames contains variable.

        :param var: Searched variable.
        :return: True if one of frames has variable otherwise false.
        """
        self.__checkFrame(var.frame)
        return self.get(var.frame).has(var.value)

    def __checkFrame(self, frameType):
        """
        Checks if frame exists.

        :param frameType: Frame type
        """
        if self.get(frameType) is None:
            frame = 'Temporary' if frameType == 'TF' else 'Local'
            self.handler.terminateInterpret(55, frame + ' frame is not set.')


class Stack(StackInterface):
    def statement(self, callStack=False):
        string = ""
        if callStack:
            for item in self.registry:
                string += '<' + str(item.opcode) + '@' + str(item.order) + '>\n'
            return string
        for item in self.registry:
            string += '<' + str(item.get('type')) + '>=' + str(item.get('value')) + '\n'
        return string


class Labels:
    def __init__(self):
        self.handler = ErrorHandler()
        self.registry = dict()

    def register(self, name, order):
        if self.has(name):
            self.handler.terminateProgram(52, 'Label already exists.')
        self.registry[name] = order

    def has(self, name):
        for label in self.registry:
            if label == name:
                return True
        return False

    def getOrder(self, name):
        if not self.has(name):
            self.handler.terminateProgram(52, 'Label does not exist.')
        return self.registry[name]

    def getAll(self):
        return self.registry

