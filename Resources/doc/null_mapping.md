Symfony-RDM – Null-Mapping
===================================

## What it does

The null mapping map's always to NULL, nothing is stored in the database for this.
This is mainly useful as a sub-mapping for other mappings.

Two use cases would be to map a deprecated option in a choice-mapping or have null-values inside an array-mapping for
specific keys.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
		<rdm:array field="data">
		    <rdm:null field="someEmptyThing" />
		    …
		</rdm:array>
		…
		<rdm:choice field="thingType" column="thing_type">
		    <rdm:option name="foo"><rdm:service id="foo_service" /></rdm:option>
		    <rdm:option name="bar"><rdm:service id="bar_service" /></rdm:option>
		    <rdm:option name="baz"><rdm:null /></rdm:option>
		</rdm:choice>
		…
	</entity>
</doctrine-mapping>
```
