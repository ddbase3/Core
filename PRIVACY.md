# Privacy and data handling: Core

Core provides the BASE3 container, classmap, request, configuration, security, logging, session, usermanagement and other default services. Actual data handling depends on which implementations the host activates. Default implementations may read HTTP inputs, session state, environment variables and local configuration and can write logs, cached class metadata and, if chosen, state, settings and databases.

The active host is responsible for determining personal-data purposes, lawful bases, recipients, persistence, retention and deletion periods. Do not assume this module's presence alone means any particular backend is enabled. See the relevant subsystem documentation and the installation's own privacy documentation for verified details.
