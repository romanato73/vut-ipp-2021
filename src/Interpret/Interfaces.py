from src.Support.ErrorHandler import ErrorHandler


class ArgumentInterface:
    type = None
    value = None

    def isInt(self):
        return True if self.type == 'int' else False

    def isBool(self):
        return True if self.type == 'bool' else False

    def isNil(self):
        return True if self.type == 'nil' else False

    def isString(self):
        return True if self.type == 'string' else False

    def isSymb(self):
        return True if self.isInt() or self.isBool() or self.isString() or self.isNil() else False

    def isRelationValid(self):
        return True if self.isInt() or self.isBool() or self.isString() else False

    def isVar(self):
        return True if self.type == 'var' else False

    def isLabel(self):
        return True if self.type == 'label' else False

    def isType(self):
        return True if self.type == 'type' else False

    def isInitialized(self):
        return True if self.value is not None else False


class StackInterface:
    handler = ErrorHandler()

    def __init__(self):
        self.registry = list()

    def push(self, item):
        """
        Push item into a stack.

        :param item: Item that is pushed into a stack
        """
        self.registry.append(item)

    def pops(self):
        """
        Pops item from a stack.

        :return: Item popped from a stack.
        """
        if len(self.registry) > 0:
            return self.registry.pop(-1)
        else:
            self.handler.terminateProgram(56, 'Can not return - stack is empty.')
