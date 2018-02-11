Symfony-RDM – Helper for using the Rich Domain Model in Symfony
===================================

This document is a detailed explanation of why this project was implemented. Maybe it helps you to decide if you want to
use it or not. (It definitely helps me keeping my head straight by writing all this stuff down.)

TL;DR: This project was implemented because I think there are still some missing pieces for one to be able to
**effectively** perform domain driven design in software based on the symfony/doctrine2 framework(s) without the
constant drive of falling back to the anti-pattern of [anemic-domain-model][2] (please do read this link).

[2]: https://martinfowler.com/bliki/AnemicDomainModel.html

If that didn't clear up things (and i don't expect it to), keep on reading. And if you know more about DDD then me and
realize that the following is just a big pile of crap, please let me know.

## A few definitions and context first

In order to understand the purpose of this project you first need to understand the context in which it operates and the
definitions and axioms it is developed under. Most importantly what domain-driven-design is (or at least how i
understand it). So let's begin with a few definitions:

With "domain" i mean a domain of knowledge, an area of expertise. Such a domain could be of any field, but in most
cases they refer to foreign (non-programming-related) fields of knowledge.

A software that tries to _purposefully_ embed knowlege of a specific domain by emulating parts of that domain is
therefore called domain-driven software. The knowledge of the domain drives the development of that kind of software.
Prime-candidates for such domain-driven software could be accounting-software, web-shops, a ticket-system or a system
to simulate how particles behave in space. Everything that emulates some part of (mental or physical) reality.
You _could_ develop any software domain-driven, but it makes IMHO more sense for high-level, non-techy cases like the
ones mentioned above then for low-level or performance-critical software like hardware-drivers, 3D-graphics-engines,
operating-systems or the likes.

The people who know a lot about the domain are called domain-experts. These are (non-technical) people that help the
developer understand how the domain works. These domain-experts could be logisticians, accounters, doctors, physicists,
mechanics or anyone that understands the domain better than the developer. In many cases the project-owners are the
domain-experts.

An important concept in DDD is that of the "model". A domain-model is a description of the part of the domain that you
or your software cares about. It is a mind-map that contains all the rules, processes and definitions that the developer
must know about to develop the software. Such a model could be expressed in text, diagrams, speech or (as many prefer)
in executable code.

## The problem this project tries to solve

In domain driven design you are supposed to put your domain logic into their semantically designated domain objects. To
put it simply: Put customer related logic in the customer related domain objects. These domain objects may be entities,
value-objects, aggregates, services or repositories.

With symfony it will be easy for most of these types of objects to put their domain logic into them because for these
the symfony-DIC helps you in composing these objects with anything they need. The objects will be fully instanciated
again for every PHP-runtime with all needed dependencies.

With (doctrine2-) entities however you may find the problem that when loaded from database the entities will not be
instanciated as new objects but re-hydrated from an empty object-skeleton with only data that originates directly from
the database. Neither the constructor nor any wakeup process runs to fill the entity with data and objects that did not
originate from the database. Without any non-database objects the entitiy is per se "stranded" and cannot communicate
with anything that is not pure data. That means that it cannot trigger domain-related processes it would need to trigger
(like sending a happy-birthday-mail) because it cannot have any link to the infrastructure objects (mailers) that it
could send these signals accross to trigger these processes.

For example: Suppose you (and your domain-experts) decide that at the point of payment the invoice should send some kind
of PDF-receipt via e-mail to some customer. In a rich-domain-model (see link above) this e-mail should be triggered by
the "invoice" entity. For the invoice entity to be able to trigger an e-mail being sent it must at some point be able to
(directly or indirectly) call some outer infrastructure services outside of the business-logic that can send e-mails.

For an entity to be able to trigger a service it must have some reference to that service. Currently when you load an
entity from doctrine2 it will only be hydrated (loaded/filled) with data from the database but nothing else. As it
currently stands, doctrine2 is not able to hydrate an entity with non-entity objects (and it should not be able to,
that's IMHO the job of the application framework which in this case is symfony). You can inject a service via the
entity-constructor into some field of an entity and persist that entity to the database but as soon as you try to load
that entity later from database via the doctrine2 ORM you will find the field empty because doctrine2 does not know
about symfony services.

That is where this project comes into play. It provides a way (or several ways even) to define how to compose your
doctrine2 entities with other things that doctrine normally does not know about. Currently it can only "store" a symfony
service in a doctrine field by static mapping, this may be extended in the future. In this mapping you tell this bundle
which field in an entity should contain what service. Then it hooks into the doctrine2 events to hydrate that fields
with the needed services when you load your entities and even check's them when you try to persist new entities. That
way you are now able to express domain logic in your entities that normally would have to be in a separate service.

## Alternatives (and why i don't like them)

There are a couple other ways to implement domain logic that would work without assigning services to entities. However
i think that for domain-related software they are all inferior to the approach hinted at by this bundle. By "domain-
related software" i mean software that tries to emulate a part of reality or mental model of a domain-expert.

The following is a list of different approaches that are alternative to the approach hinted at by this bundle:

### No domain model at all

The most obvious alternative is to just not have an explicit model of the domain in the software at all. A domain-model
inside a software is/are in most cases a mental model represented by some seperated piece(s) of logic (classes, methods,
functions, ...) whose whole single purpose is to simulate the domain (e.g.: logistics, accounting, medicine, physics,
machinery, ...) as described by the domain-experts (e.g.: logisticians, accounters, doctors, physicists, mechanics,
...). One of the most important attributes of such a domain-model in software is the absence of any infrastructure logic
(file-handling, rendering, I/O, request-/response-handling, user-management, dependency-management, ...) in it.

Not having a distinct domain-model in domain-related software means that the domain logic is mingled with other non-
domain logic. In such a software a change to the domain means that you also have to change parts of the that is not
relevant to the domain and in reverse a change to the non-domain logic means that you also have to change domain logic.

For small software-projects this might be doable, but for bigger projects things will become hard to maintain very fast.
And even if it's a small project now, you never know if your small project evolves into something much bigger. So why
not just do it right from the beginning and use a dedicated domain-layer?

### A non-rich or anemic domain model

### The command pattern

### Manually setting services on entities when loading them (f.e.: in repositories)
