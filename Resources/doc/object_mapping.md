Symfony-RDM – Object-Mapping
===================================

## What it does

The object mapping allows you to map an object (from a field of an entity) to the database.

You can specify how to instantiate (create / factorize) the object when loading from the database and how to serialize
an existing value to store it in the database by specifiying custom factories and serializer methods.

All fields inside the mapped object can be mapped using their own mappings.

In contrast to doctrine embeddables this object mapping is not based on the class-name, so one class can be mapped in
many different ways. You can even specify to map to an interface or abstract class if you use a custom factory.
