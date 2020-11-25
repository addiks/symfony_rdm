Symfony-RDM – Nullable-Mapping
===================================

## What it does

The nullable mapping allows to have any other mapping nullable (map to null) depending on a column in the database.

Attributes:
 - field (required): The field of the entity 
 - column (optional): name of the column to use - can be an extra-column to store if the value is null or not (useful if the contained mapping consists of many columns)
 - strict (optional): only evaluate as null if the column-value is null (useful if the column is the same as the column containing the value of the contained mapping) **Do not use together with column**, as column to set the column-value to '0' if the value is null!

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
