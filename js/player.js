/**
 * @file
 * Audio player functionality for name pronunciation field.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.namePronunciationPlayer = {
    attach: function (context, settings) {
      const players = context.querySelectorAll('.name-pronunciation-player');

      players.forEach(function (playerElement) {
        // Skip if already initialized.
        if (playerElement.dataset.initialized) {
          return;
        }
        playerElement.dataset.initialized = 'true';

        const playButton = playerElement.querySelector('.pronunciation-play-button');
        const audioElement = playerElement.querySelector('audio');

        if (!playButton || !audioElement) {
          return;
        }

        // Play button handler.
        playButton.addEventListener('click', function (e) {
          e.preventDefault();

          if (audioElement.paused) {
            // Handle the promise returned by play() to catch errors.
            const playPromise = audioElement.play();

            if (playPromise !== undefined) {
              playPromise
                .then(function() {
                  // Playback started successfully.
                  playButton.classList.add('playing');
                  playButton.setAttribute('aria-label', 'Pause pronunciation');
                })
                .catch(function(error) {
                  // Auto-play was prevented or another error occurred.
                  console.error('Audio playback failed:', error);
                  playButton.disabled = true;
                  playButton.classList.add('error');
                  playButton.setAttribute('aria-label', 'Audio playback failed');
                });
            }
          } else {
            audioElement.pause();
            playButton.classList.remove('playing');
            playButton.setAttribute('aria-label', 'Play pronunciation');
          }
        });

        // Reset button state when audio ends.
        audioElement.addEventListener('ended', function () {
          playButton.classList.remove('playing');
          playButton.setAttribute('aria-label', 'Play pronunciation');
        });

        // Handle audio errors.
        audioElement.addEventListener('error', function () {
          playButton.disabled = true;
          playButton.classList.add('error');
          playButton.setAttribute('aria-label', 'Audio unavailable');
        });
      });
    }
  };

})(Drupal);
