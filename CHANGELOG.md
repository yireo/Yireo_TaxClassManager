# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 27 November 2026
### Added
- Admin grid and form to manage customer and product tax classes, built on Loki AdminComponents
- Row and mass delete of tax classes from the grid
- Deleting a tax class that is configured as a default tax class is refused
- Documentation of the required ACL permissions and delete restrictions

### Changed
- Minimum PHP version raised to 8.2 and Loki AdminComponents 0.9.2 required
- Dropped the unused Loki_Base and Loki_CssUtils dependencies, added the missing psr/log dependency

### Fixed
- Grid columns were rendered twice once a grid bookmark existed
- Inline editing and row/mass delete in the grid now require the save and delete permissions
- Deleting via the edit form now requires the delete permission and only works via POST
- The class type of an existing tax class can no longer be changed via a crafted form request, and is reliably kept when saving
- Mass delete deduplicates and caps the submitted tax class IDs
