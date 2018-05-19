Symfony-RDM – Array-Mapping
===================================

## What it does

The array mapping allows you to map a static-size array with predefined keys onto the database.

## XML Mapping

```xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping
    xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:rdm="https://github.com/addiks/symfony_rdm/raw/master/Resources/mapping-schema.v1.xsd"
>
	<entity name="Foo\Bar\SomeEntity" table="some_entity">
		…
		<!-- The field $settings on the entity containing an associative array with different types of values -->
		<rdm:array field="settings">
		    <field name="cached" type="boolean" />
		    <field name="username" type="string" />
		    <field name="password" type="string" />
		    <field name="uri" type="string" />
		    <rdm:object field="converter" class="Foo\Bar">
		        <field name="length" type="integer" />
		    </rdm:object>
		</rdm:array>
		…
	</entity>
</doctrine-mapping>
```
