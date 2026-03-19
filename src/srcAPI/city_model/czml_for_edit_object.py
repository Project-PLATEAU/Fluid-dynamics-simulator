import abc

class ICzmlForEditObject(metaclass=abc.ABCMeta):
    @abc.abstractmethod
    def load(self):
        raise NotImplementedError("This method should be overridden in subclasses")

    @abc.abstractmethod
    def remove(self):
        raise NotImplementedError("This method should be overridden in subclasses")

    @abc.abstractmethod
    def create(self):
        raise NotImplementedError("This method should be overridden in subclasses")