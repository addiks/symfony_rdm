Symfony-RDM – Nullable-Mapping
===================================

## What it does

The nullable mapping allows to have any other mapping nullable (map to null) depending on a column in the database.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
		<rdm:nullable field="bar">
		    <rdm:object class="Foo\Bar" />
		</rdm:nullable>
		…
	</entity>
</doctrine-mapping>
```
