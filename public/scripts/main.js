// Event edit and delete functions for dashboard
function editEvent(eventId) {
    window.location.href = `/edit-event?id=${eventId}`;
}

async function deleteEvent(eventId) {
    if (!confirm('Czy na pewno chcesz usunąć to wydarzenie? Tej akcji nie można cofnąć.')) {
        return;
    }

    try {
        const response = await fetch('/api/event/delete', {
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
        if (data.success) {
            alert('Wydarzenie zostało usunięte!');
            // Refresh the page to update the dashboard
            location.reload();
        } else {
            alert('Wystąpił błąd podczas usuwania wydarzenia.');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        alert('Wystąpił błąd podczas usuwania wydarzenia.');
    }
}

async function acceptEvent(eventId) {
    if (!confirm('Czy na pewno chcesz zaakceptować to wydarzenie?')) {
        return;
    }

    try {
        const response = await fetch('/api/event/accept', {
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
        if (data.success) {
            alert('Wydarzenie zostało zaakceptowane!');
            // Refresh the page to update the dashboard
            location.reload();
        } else {
            alert('Wystąpił błąd podczas akceptowania wydarzenia.');
        }
    } catch (error) {
        console.error('Error accepting event:', error);
        alert('Wystąpił błąd podczas akceptowania wydarzenia.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Only proceed if eventsData is defined (i.e., on pages that load events)
    if (typeof eventsData === 'undefined') {
        return;
    }

    // Populate location suggestions
    const locationSuggestions = document.getElementById('location-suggestions');
    if (locationSuggestions) {
        const uniqueLocations = [...new Set(eventsData.map(event => event.location))];
        uniqueLocations.forEach(location => {
            const option = document.createElement('option');
            option.value = location;
            locationSuggestions.appendChild(option);
        });
    }

    // Get elements (only if they exist on the page)
    const searchInput = document.getElementById('search-input');
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
    if (locationFilter) {
        locationFilter.addEventListener('click', () => {
            locationInputContainer.style.display = locationInputContainer.style.display === 'none' ? 'block' : 'none';
            dateInputContainer.style.display = 'none'; // Hide date input
            updateFilterHighlights();
            filterEvents();
        });
    }

    // Toggle date input
    if (dateFilter) {
        dateFilter.addEventListener('click', () => {
            dateInputContainer.style.display = dateInputContainer.style.display === 'none' ? 'block' : 'none';
            locationInputContainer.style.display = 'none'; // Hide location input
            updateFilterHighlights();
            filterEvents();
        });
    }

    // Saved filter
    if (savedFilter) {
        savedFilter.addEventListener('click', () => {
            isSavedFilterActive = !isSavedFilterActive;
            locationInputContainer.style.display = 'none';
            dateInputContainer.style.display = 'none';
            updateFilterHighlights();
            filterEvents();
        });
    }

    // Filter events function
    function filterEvents() {
        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        const locationValue = locationInput ? locationInput.value.toLowerCase() : '';
        const dateValue = dateInput ? dateInput.value : '';
        const showSavedOnly = isSavedFilterActive;

        eventCards.forEach(card => {
            const eventId = card.querySelector('input[type="checkbox"]').id;
            const event = eventsData.find(e => e.id === eventId);
            if (!event) return;

            let show = true;

            // Search filter (title)
            if (searchValue && !event.title.toLowerCase().includes(searchValue)) {
                show = false;
            }

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
    if (searchInput) searchInput.addEventListener('input', filterEvents);
    if (locationInput) locationInput.addEventListener('input', filterEvents);
    if (dateInput) dateInput.addEventListener('change', filterEvents);

    // Function to parse date from DD.MM.YYYY to YYYY-MM-DD
    function parseDate(dateStr) {
        const parts = dateStr.split('.');
        return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
    }

    // Handle interest checkbox changes
    eventCards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        if (!checkbox) return;
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

                // Update interest count
                let interestCountSpan = document.querySelector('.event-detail-footer .interest-count');
                if (data.interestCount > 0) {
                    if (interestCountSpan) {
                        interestCountSpan.textContent = `LICZBA ZAINTERESOWANYCH: ${data.interestCount}`;
                    } else {
                        // Create new interest count span if it doesn't exist
                        interestCountSpan = document.createElement('span');
                        interestCountSpan.className = 'interest-count';
                        interestCountSpan.textContent = `LICZBA ZAINTERESOWANYCH: ${data.interestCount}`;
                        document.querySelector('.event-detail-footer .checkbox-wrapper').after(interestCountSpan);
                    }
                } else {
                    if (interestCountSpan) {
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

// Friend management functions
async function searchUsers() {
    const query = document.getElementById('user-search').value;
    const resultsDiv = document.getElementById('search-results');

    if (query.length < 2) {
        resultsDiv.innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`/api/friends/search?query=${encodeURIComponent(query)}`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const users = await response.json();
        resultsDiv.innerHTML = '';

        users.forEach(user => {
            const userDiv = document.createElement('div');
            userDiv.className = 'user-result';
            userDiv.innerHTML = `
                <span>${user.name} ${user.surname}</span>
                <button onclick="sendFriendRequest('${user.id}')">WYŚLIJ ZAPROSZENIE</button>
            `;
            resultsDiv.appendChild(userDiv);
        });
    } catch (error) {
        console.error('Error searching users:', error);
    }
}

async function sendFriendRequest(friendId) {
    try {
        const response = await fetch('/api/friends/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `friendId=${encodeURIComponent(friendId)}`
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        if (data.success) {
            alert('Zaproszenie zostało wysłane!');
            // Refresh the page to update the UI
            location.reload();
        } else {
            alert('Wystąpił błąd podczas wysyłania zaproszenia.');
        }
    } catch (error) {
        console.error('Error sending friend request:', error);
        alert('Wystąpił błąd podczas wysyłania zaproszenia.');
    }
}

async function acceptFriendRequest(requestId) {
    try {
        const response = await fetch('/api/friends/accept', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `requestId=${encodeURIComponent(requestId)}`
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        if (data.success) {
            alert('Zaproszenie zostało zaakceptowane!');
            // Refresh the page to update the UI
            location.reload();
        } else {
            alert('Wystąpił błąd podczas akceptowania zaproszenia.');
        }
    } catch (error) {
        console.error('Error accepting friend request:', error);
        alert('Wystąpił błąd podczas akceptowania zaproszenia.');
    }
}

async function rejectFriendRequest(requestId) {
    try {
        const response = await fetch('/api/friends/reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `requestId=${encodeURIComponent(requestId)}`
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        if (data.success) {
            alert('Zaproszenie zostało odrzucone!');
            // Refresh the page to update the UI
            location.reload();
        } else {
            alert('Wystąpił błąd podczas odrzucania zaproszenia.');
        }
    } catch (error) {
        console.error('Error rejecting friend request:', error);
        alert('Wystąpił błąd podczas odrzucania zaproszenia.');
    }
}

async function removeFriend(friendId) {
    if (!confirm('Czy na pewno chcesz usunąć tego znajomego?')) {
        return;
    }

    try {
        const response = await fetch('/api/friends/remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `friendId=${encodeURIComponent(friendId)}`
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        if (data.success) {
            alert('Znajomy został usunięty!');
            // Refresh the page to update the UI
            location.reload();
        } else {
            alert('Wystąpił błąd podczas usuwania znajomego.');
        }
    } catch (error) {
        console.error('Error removing friend:', error);
        alert('Wystąpił błąd podczas usuwania znajomego.');
    }
}

// Event edit and delete functions for dashboard
function editEvent(eventId) {
    window.location.href = `/edit-event?id=${eventId}`;
}

async function deleteEvent(eventId) {
    if (!confirm('Czy na pewno chcesz usunąć to wydarzenie? Tej akcji nie można cofnąć.')) {
        return;
    }

    try {
        const response = await fetch('/api/event/delete', {
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
        if (data.success) {
            alert('Wydarzenie zostało usunięte!');
            // Refresh the page to update the dashboard
            location.reload();
        } else {
            alert('Wystąpił błąd podczas usuwania wydarzenia.');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        alert('Wystąpił błąd podczas usuwania wydarzenia.');
    }
}

});
