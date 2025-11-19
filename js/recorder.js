/**
 * @file
 * Audio recorder functionality for name pronunciation field.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.namePronunciationRecorder = {
    attach: function (context, settings) {
      const recorders = context.querySelectorAll('.name-pronunciation-recorder');

      recorders.forEach(function (recorderElement) {
        // Skip if already initialized.
        if (recorderElement.dataset.initialized) {
          return;
        }
        recorderElement.dataset.initialized = 'true';

        const recordButton = recorderElement.querySelector('.recorder-record');
        const stopButton = recorderElement.querySelector('.recorder-stop');
        const statusElement = recorderElement.querySelector('.status-message');
        const previewContainer = recorderElement.querySelector('.recorder-preview');
        const audioDataField = recorderElement.closest('.field--widget-name-pronunciation-recorder').querySelector('.audio-data-field');

        let mediaRecorder;
        let audioChunks = [];
        let stream;

        // Record button handler.
        recordButton.addEventListener('click', async function (e) {
          e.preventDefault();

          try {
            // Request microphone access.
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });

            // Determine the best MIME type to use.
            const mimeTypes = [
              'audio/webm',
              'audio/webm;codecs=opus',
              'audio/ogg;codecs=opus',
              'audio/mp4',
              'audio/mpeg'
            ];

            let selectedMimeType = '';
            for (const mimeType of mimeTypes) {
              if (MediaRecorder.isTypeSupported(mimeType)) {
                selectedMimeType = mimeType;
                break;
              }
            }

            if (!selectedMimeType) {
              throw new Error('No supported audio MIME type found');
            }

            mediaRecorder = new MediaRecorder(stream, { mimeType: selectedMimeType });
            audioChunks = [];

            mediaRecorder.addEventListener('dataavailable', function (event) {
              audioChunks.push(event.data);
            });

            mediaRecorder.addEventListener('stop', function () {
              const audioBlob = new Blob(audioChunks, { type: selectedMimeType });

              // Convert to base64 for form submission.
              const reader = new FileReader();
              reader.readAsDataURL(audioBlob);
              reader.onloadend = function () {
                audioDataField.value = reader.result;
              };

              // Create preview audio element.
              const audioUrl = URL.createObjectURL(audioBlob);
              previewContainer.innerHTML = '<audio controls src="' + audioUrl + '"></audio>';
              previewContainer.classList.remove('hidden');

              // Stop all tracks.
              if (stream) {
                stream.getTracks().forEach(track => track.stop());
              }

              // Update UI.
              statusElement.textContent = 'Recording saved. Remember to save the form.';
              recordButton.classList.remove('hidden');
              stopButton.classList.add('hidden');
            });

            mediaRecorder.start();

            // Update UI.
            statusElement.textContent = 'Recording...';
            recordButton.classList.add('hidden');
            stopButton.classList.remove('hidden');

          } catch (error) {
            console.error('Error accessing microphone:', error);
            statusElement.textContent = 'Error: Could not access microphone. Please check your browser permissions.';
          }
        });

        // Stop button handler.
        stopButton.addEventListener('click', function (e) {
          e.preventDefault();

          if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
          }
        });
      });
    }
  };

})(Drupal);
