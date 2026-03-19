import abc

class IThreeDModelObjectList(abc.ABC):
    @abc.abstractmethod
    def set_objects_details(self):
        raise NotImplementedError("This method should be overridden in subclasses")

    @abc.abstractmethod
    def export_to_objects_file(self):
        raise NotImplementedError("This method should be overridden in subclasses")

    @abc.abstractmethod
    def export_to_czml(self):
        raise NotImplementedError("This method should be overridden in subclasses")
