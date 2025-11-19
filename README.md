# Name Pronunciation

A Drupal module that provides a custom field type for recording and playing audio pronunciations of names.

## Overview

The Name Pronunciation module allows users to record audio pronunciations of first names, last names, or full names directly from their browser using their device's microphone. These recordings can be added when editing nodes in the Drupal admin interface and played back by site visitors with a simple button interface.

## Features

- **Browser-based Recording**: Record audio directly from your computer or phone's microphone
- **Easy Integration**: Add the pronunciation field to any content type
- **Simple Playback**: Site visitors can play pronunciations with a single click on a speaker icon button
- **Multiple Audio Format Support**: Automatically uses the best supported audio format for the user's browser (WebM, OGG, MP4, etc.)
- **Optional Descriptions**: Add context like "First name", "Last name", or "Full name"
- **Preview Before Saving**: Hear your recording before submitting the form

## Requirements

- Drupal 10 or Drupal 11
- A web browser that supports the MediaRecorder API (most modern browsers)

## Installation

1. Download and place this module in your `/modules/custom` directory
2. Enable the module via Drush: `drush en name_pronunciation`
3. Or enable via the Drupal admin interface at `admin/modules`

## Usage

### Adding the Field to a Content Type

1. Navigate to **Structure** > **Content types**
2. Select **Manage fields** for your desired content type
3. Click **Add field**
4. Select **Name Pronunciation** as the field type
5. Configure the field settings as needed
6. Save the field configuration

### Recording a Pronunciation

1. Edit or create a node with the pronunciation field
2. Click the **Record** button in the pronunciation field
3. Allow browser access to your microphone when prompted
4. Speak the name pronunciation
5. Click the **Stop** button when finished
6. Preview the recording to verify quality
7. Optionally add a description (e.g., "First name", "Last name")
8. Save the node

### Viewing a Pronunciation

When viewing a node with a pronunciation recording:
- A speaker icon button will be displayed
- Click the button to play the audio pronunciation
- The button includes visual feedback during playback

## Module Structure

### Core Module Files

```
name_pronunciation.info.yml       # Module metadata
name_pronunciation.module         # Hook implementations
name_pronunciation.libraries.yml  # JavaScript and CSS library definitions
```

### PHP Classes

```
src/Plugin/Field/FieldType/NamePronunciationItem.php
  - Custom field type that stores audio file references
  - Includes optional description property
  - Extends EntityReferenceItem for file handling

src/Plugin/Field/FieldWidget/NamePronunciationRecorderWidget.php
  - Recording interface for node edit forms
  - Handles file upload and storage
  - Provides preview functionality

src/Plugin/Field/FieldFormatter/NamePronunciationPlayerFormatter.php
  - Playback interface for displaying pronunciations
  - Configurable button text and description display
  - Clean, accessible player controls
```

### Frontend Assets

```
js/recorder.js
  - MediaRecorder API implementation
  - Captures audio from user's microphone
  - Handles browser compatibility for audio formats
  - Creates preview player
  - Converts audio to base64 for form submission

js/player.js
  - Audio playback controls
  - Play/pause button functionality
  - Visual feedback for playing state
  - Error handling for unavailable audio

css/pronunciation.css
  - Styling for recorder interface
  - Styling for player button (speaker icon)
  - Responsive and accessible design

templates/name-pronunciation-audio-player.html.twig
  - Template for the audio player display
  - Accessible markup with ARIA labels
  - Support for optional description text
```

## Technical Details

### Recording Implementation

- Uses the browser's native **MediaRecorder API**
- Automatically detects and uses the best supported format:
  - `audio/webm` (preferred)
  - `audio/webm;codecs=opus`
  - `audio/ogg;codecs=opus`
  - `audio/mp4`
  - `audio/mpeg`
- Audio data is converted to base64 for form submission
- Files are stored in the `public://pronunciations/` directory

### File Storage

- Audio files are managed through Drupal's file system
- Files are stored as file entities with proper references
- Unique filenames prevent conflicts: `pronunciation_[timestamp]_[uniqid].[extension]`

### Browser Compatibility

Works with any modern browser that supports:
- MediaRecorder API (for recording)
- HTML5 Audio (for playback)

Supported browsers include:
- Chrome/Edge 49+
- Firefox 25+
- Safari 14+
- Opera 36+

## Field Settings

### Field Configuration

- **File extensions**: Configure which audio formats are allowed (default: webm, ogg, mp3, mp4, m4a, wav)
- **Maximum duration**: Set recording length limits (default: 10 seconds)

### Formatter Settings

- **Show description**: Toggle display of the description text
- **Button text**: Customize the accessible button text (default: "Listen to pronunciation")

## Accessibility

- Keyboard accessible controls
- ARIA labels for screen readers
- Visual feedback for recording and playback states
- Semantic HTML markup

## Security

- Microphone access requires user permission
- Files are validated and processed through Drupal's file API
- Proper sanitization of user input

## Development Commands

```bash
# Clear Drupal cache
drush cr

# Enable the module
drush en name_pronunciation

# Uninstall the module
drush pmu name_pronunciation
```

## Support

For bug reports and feature requests, please use the project's issue queue.

## License

This project is licensed under the GPL-2.0-or-later license.
