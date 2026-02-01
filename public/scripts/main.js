document.addEventListener('DOMContentLoaded', () => {
    // Populate location suggestions
    const locationSuggestions = document.getElementById('location-suggestions');
    const uniqueLocations = [...new Set(eventsData.map(event => event.location))];
    uniqueLocations.forEach(location => {
        const option = document.createElement('option');
        option.value = location;
        locationSuggestions.appendChild(option);
    });

    // Get elements
    const locationFilter = document.getElementById('location-filter');
    const savedFilter = document.getElementById('saved-filter');
    const dateFilter = document.getElementById('date-filter');
    const locationInputContainer = document.getElementById('location-input-container');
    const dateInputContainer = document.getElementById('date-input-container');
    const locationInput = document.getElementById('location-input');
    const dateInput = document.getElementById('date-input');
    const eventCards = document.querySelectorAll('.event-card');

    // Filter states
    let isSavedFilterActive = false;

    // Toggle location input
    locationFilter.addEventListener('click', () => {
        locationInputContainer.style.display = locationInputContainer.style.display === 'none' ? 'block' : 'none';
        dateInputContainer.style.display = 'none'; // Hide date input
        updateFilterHighlights();
        filterEvents();
    });

    // Toggle date input
    dateFilter.addEventListener('click', () => {
        dateInputContainer.style.display = dateInputContainer.style.display === 'none' ? 'block' : 'none';
        locationInputContainer.style.display = 'none'; // Hide location input
        updateFilterHighlights();
        filterEvents();
    });

    // Saved filter
    savedFilter.addEventListener('click', () => {
        isSavedFilterActive = !isSavedFilterActive;
        locationInputContainer.style.display = 'none';
        dateInputContainer.style.display = 'none';
        updateFilterHighlights();
        filterEvents();
    });

    // Filter events function
    function filterEvents() {
        const locationValue = locationInput.value.toLowerCase();
        const dateValue = dateInput.value;
        const showSavedOnly = isSavedFilterActive;

        eventCards.forEach(card => {
            const eventId = card.querySelector('input[type="checkbox"]').id;
            const event = eventsData.find(e => e.id === eventId);
            if (!event) return;

            let show = true;

            // Location filter
            if (locationValue && !event.location.toLowerCase().includes(locationValue)) {
                show = false;
            }

            // Date filter
            if (dateValue && parseDate(event.date) !== dateValue) {
                show = false;
            }

            // Saved filter
            if (showSavedOnly && !card.querySelector('input[type="checkbox"]').checked) {
                show = false;
            }

            card.style.display = show ? 'flex' : 'none';
        });
    }

    // Update filter highlights
    function updateFilterHighlights() {
        const isLocationActive = locationInputContainer.style.display === 'block';
        const isDateActive = dateInputContainer.style.display === 'block';
        const isSavedActive = isSavedFilterActive;

        const filterBox = document.querySelector('.filter-box');
        filterBox.classList.toggle('location-active', isLocationActive);
        filterBox.classList.toggle('saved-active', isSavedActive);
        filterBox.classList.toggle('date-active', isDateActive);
    }

    // Event listeners for inputs
    locationInput.addEventListener('input', filterEvents);
    dateInput.addEventListener('change', filterEvents);

    // Function to parse date from DD.MM.YYYY to YYYY-MM-DD
    function parseDate(dateStr) {
        const parts = dateStr.split('.');
        return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
    }

    // Handle interest checkbox changes
    eventCards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        const eventId = checkbox.id;

        checkbox.addEventListener('change', async () => {
            try {
                const response = await fetch('/api/interest/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `eventId=${encodeURIComponent(eventId)}`
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                // Update checkbox state
                checkbox.checked = data.isInterested;

                // Update interest count
                let interestCountSpan = card.querySelector('.interest-count');
                if (data.interestCount > 0) {
                    if (interestCountSpan) {
                        interestCountSpan.textContent = `LICZBA ZAINTERESOWANYCH: ${data.interestCount}`;
                    } else {
                        // Create new interest count span if it doesn't exist
                        interestCountSpan = document.createElement('span');
                        interestCountSpan.className = 'interest-count';
                        interestCountSpan.textContent = `LICZBA ZAINTERESOWANYCH: ${data.interestCount}`;
                        card.querySelector('.checkbox-wrapper').after(interestCountSpan);
                    }
                } else {
                    if (interestCountSpan) {
                        interestCountSpan.remove();
                    }
                }
            } catch (error) {
                console.error('Error toggling interest:', error);
                // Revert checkbox state on error
                checkbox.checked = !checkbox.checked;
                alert('Wystąpił błąd podczas aktualizacji zainteresowania. Spróbuj ponownie.');
            }
        });
    });

    // Handle interest checkbox changes in event details
    const eventDetailCheckbox = document.querySelector('.event-detail-card input[type="checkbox"]');
    if (eventDetailCheckbox) {
        eventDetailCheckbox.addEventListener('change', async () => {
            const eventId = eventDetailCheckbox.id;
            try {
                const response = await fetch('/api/interest/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `eventId=${encodeURIComponent(eventId)}`
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                // Update checkbox state
                eventDetailCheckbox.checked = data.isInterested;

                // Update interest count in event details
                let interestCountSpan = document.querySelector('.event-detail-info p:last-child');
                if (data.interestCount > 0) {
                    if (interestCountSpan && interestCountSpan.textContent.includes('Liczba zainteresowanych')) {
                        interestCountSpan.textContent = `Liczba zainteresowanych: ${data.interestCount}`;
                    } else {
                        // Create new interest count p if it doesn't exist
                        const newP = document.createElement('p');
                        newP.innerHTML = `<i class="fa-solid fa-users"></i> Liczba zainteresowanych: ${data.interestCount}`;
                        document.querySelector('.event-detail-info').appendChild(newP);
                    }
                } else {
                    if (interestCountSpan && interestCountSpan.textContent.includes('Liczba zainteresowanych')) {
                        interestCountSpan.remove();
                    }
                }
            } catch (error) {
                console.error('Error toggling interest:', error);
                // Revert checkbox state on error
                eventDetailCheckbox.checked = !eventDetailCheckbox.checked;
                alert('Wystąpił błąd podczas aktualizacji zainteresowania. Spróbuj ponownie.');
            }
        });
    }

    // Handle share button clicks
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('share-btn') || e.target.closest('.share-btn')) {
            e.preventDefault(); // Prevent navigating to event details
            e.stopPropagation(); // Prevent bubbling to parent elements
            const url = e.target.getAttribute('data-url') || e.target.closest('.share-btn').getAttribute('data-url');
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    const message = document.getElementById('share-message');
                    if (message) {
                        message.classList.add('show');
                        setTimeout(() => {
                            message.classList.remove('show');
                        }, 2000);
                    }
                }).catch(err => {
                    console.error('Failed to copy with clipboard API: ', err);
                    // Fallback to execCommand
                    fallbackCopyTextToClipboard(url);
                });
            } else {
                // Fallback for browsers without clipboard API
                fallbackCopyTextToClipboard(url);
            }
        }
    });

// Fallback function to copy text to clipboard
function fallbackCopyTextToClipboard(text) {
    const input = document.createElement("input");
    input.value = text;
    input.style.position = "fixed";
    input.style.top = "0";
    input.style.left = "0";
    input.style.width = "1px";
    input.style.height = "1px";
    input.style.opacity = "0";
    document.body.appendChild(input);
    input.focus();
    input.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            const message = document.getElementById('share-message');
            message.classList.add('show');
            setTimeout(() => {
                message.classList.remove('show');
            }, 2000);
        } else {
            alert('Nie udało się skopiować linku.');
        }
    } catch (err) {
        console.error('Fallback copy failed: ', err);
        alert('Nie udało się skopiować linku.');
    }
    document.body.removeChild(input);
}
});
