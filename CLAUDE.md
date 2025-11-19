# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Drupal module for Drupal 10 and 11 that provides a custom field type for storing and playing audio pronunciations of names.

## Module Purpose

The module allows users to record audio pronunciations of first names, last names, or full names directly from the browser using their device's microphone. These recordings can be added when editing nodes in the Drupal admin interface.

### Key Features

- **Recording Interface**: When editing a node, users can click a button to record a short audio clip using their computer or phone microphone
- **Field Storage**: Custom field type that stores audio files containing name pronunciations
- **Playback Interface**: When viewing a node with a pronunciation field that has a value, displays a speaker icon button with a pronunciation indicator
- **Browser Compatibility**: Audio files are stored in a format playable by all modern browsers

## Development Context

### Drupal Version Compatibility
- Target: Drupal 10 and Drupal 11
- This is a custom field module

### Standard Drupal Module Structure
```
/
├── name_pronunciation.info.yml       # Module metadata
├── name_pronunciation.module         # Hook implementations
├── src/
│   ├── Plugin/
│   │   └── Field/
│   │       ├── FieldType/           # Custom field type definition
│   │       ├── FieldWidget/         # Recording interface for edit forms
│   │       └── FieldFormatter/      # Playback interface for display
```

### Key Implementation Areas

1. **Field Type**: Define custom field storage for audio file references
2. **Field Widget**: Browser-based audio recording interface using Web Audio API or MediaRecorder API
3. **Field Formatter**: Playback button with speaker icon for displaying recorded pronunciations
4. **File Management**: Handle audio file uploads and storage through Drupal's file system

### Audio Considerations
- Use browser-native APIs (MediaRecorder API) for recording
- Store files in web-compatible formats (WebM, MP4/AAC, or OGG)
- Consider fallback formats for browser compatibility
- Implement reasonable recording length limits

### Common Drupal Development Commands
```bash
# Clear Drupal cache
drush cr

# Enable the module
drush en name_pronunciation

# Uninstall the module
drush pmu name_pronunciation
```
