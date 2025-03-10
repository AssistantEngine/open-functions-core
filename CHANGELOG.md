# Changelog

All notable changes to `open-functions-core` will be documented in this file.

## Version 1.1.0 - 2025-03-10

### Added
- **Registry as an OpenFunction:** The OpenFunction Registry now implements the OpenFunction interface, allowing it to be invoked just like any other Open Function.
- **Meta-mode:** Introduced a meta-mode that registers only three core registry functions initially - `listFunctions`, `activateFunction`, and `deactivateFunction`. This mode enables the LLM to dynamically activate or deactivate additional methods as needed, keeping the tool definitions concise and manageable.
- **Dedicated Registry Presenter:** Added a dedicated Registry Presenter that implements the MessageListExtensionInterface. This presenter automatically prepends a developer message with namespace details to the conversation message list, offering better context for the LLM.