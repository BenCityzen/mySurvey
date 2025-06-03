document.addEventListener('DOMContentLoaded', function () {
    // Track survey start time
    if (window.location.pathname.includes('survey.php')) {
        // Store start time
        localStorage.setItem('surveyStartTime', Date.now());
    }

    // Other food checkbox functionality
    const otherCheckbox = document.querySelector('input[value="other"]');
    const otherFoodInput = document.getElementById('other_food');
    
    if (otherCheckbox && otherFoodInput) {
        otherCheckbox.addEventListener('change', function() {
            otherFoodInput.disabled = !this.checked;
            if (!this.checked) otherFoodInput.value = '';
        });
    }

    // Form validation
    const form = document.getElementById('surveyForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Age validation
            const dobInput = document.getElementById('dob');
            if (dobInput && dobInput.value) {
                const dob = new Date(dobInput.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                if (age < 5 || age > 120) {
                    alert('Age must be between 5 and 120 years.');
                    e.preventDefault();
                    return false;
                }
            }
            
            // Food selection validation
            const foodCheckboxes = document.querySelectorAll('input[name="food[]"]:checked');
            if (foodCheckboxes.length === 0) {
                alert('Please select at least one favorite food.');
                e.preventDefault();
                return false;
            }
            
            // Other food validation
            if (otherCheckbox && otherCheckbox.checked && otherFoodInput && !otherFoodInput.value.trim()) {
                alert('Please specify your other favorite food.');
                e.preventDefault();
                return false;
            }
            
            // Track survey completion
            if (window.location.pathname.includes('survey.php')) {
                const startTime = localStorage.getItem('surveyStartTime');
                if (startTime) {
                    const durationSeconds = Math.round((Date.now() - parseInt(startTime)) / 1000);
                    
                    // Send to Google Analytics
                    if (typeof gtag === 'function') {
                        gtag('event', 'survey_complete', {
                            'event_category': 'survey_timing',
                            'event_label': 'Survey Completion',
                            'value': durationSeconds
                        });
                    }
                    
                    // Clear the timer
                    localStorage.removeItem('surveyStartTime');
                }
            }
        });
    }
});
