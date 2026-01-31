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
});

// Toggle date input
dateFilter.addEventListener('click', () => {
    dateInputContainer.style.display = dateInputContainer.style.display === 'none' ? 'block' : 'none';
    locationInputContainer.style.display = 'none'; // Hide location input
    updateFilterHighlights();
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
    const showSavedOnly = locationInputContainer.style.display === 'none' && dateInputContainer.style.display === 'none';

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
        if (dateValue && event.date !== dateValue) {
            show = false;
        }

        // Saved filter
        if (showSavedOnly) {
            const checkbox = card.querySelector('input[type="checkbox"]');
            if (!checkbox.checked) {
                show = false;
            }
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
