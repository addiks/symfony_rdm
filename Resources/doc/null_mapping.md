Symfony-RDM – Null-Mapping
===================================

## What it does

The null mapping map's always to NULL, nothing is stored in the database for this.
This is mainly useful as a sub-mapping for other mappings.

Two use cases would be to map a deprecated option in a choice-mapping or have null-values inside an array-mapping for
specific keys.
