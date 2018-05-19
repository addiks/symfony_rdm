Symfony-RDM – Object-Mapping
===================================

## What it does

The object mapping allows you to map an object (from a field of an entity) to the database.

You can specify how to instantiate (create / factorize) the object when loading from the database and how to serialize
an existing value to store it in the database by specifiying custom factories and serializer methods.

All fields inside the mapped object can be mapped using their own mappings.

In contrast to doctrine embeddables this object mapping is not based on the class-name, so one class can be mapped in
many different ways. You can even specify to map to an interface or abstract class if you use a custom factory.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
		<rdm:object
		    class="Foo\Bar"
		    field="bar"
		>
		    <field type="string" name="lorem" />
		    <field type="string" name="ipsum" />
		</rdm:object>
		…
		<rdm:object class="Value\EAN" factory="self::factorize" serialize="$this->__toString" />
		…
	</entity>
</doctrine-mapping>
```
