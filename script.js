document.addEventListener('DOMContentLoaded', function() {
    // Enable/disable other food text input based on checkbox
    const otherCheckbox = document.querySelector('input[value="other"]');
    const otherFoodInput = document.getElementById('other_food');
    
    otherCheckbox.addEventListener('change', function() {
        otherFoodInput.disabled = !this.checked;
        if (!this.checked) otherFoodInput.value = '';
    });
    
    // Age validation
    const dobInput = document.getElementById('dob');
    const form = document.getElementById('surveyForm');
    
    form.addEventListener('submit', function(e) {
        // Validate age
        if (dobInput.value) {
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
        
        // Validate at least one food selected
        const foodCheckboxes = document.querySelectorAll('input[name="food[]"]:checked');
        if (foodCheckboxes.length === 0) {
            alert('Please select at least one favorite food.');
            e.preventDefault();
            return false;
        }
        
        // Validate other food if checked
        if (otherCheckbox.checked && !otherFoodInput.value.trim()) {
            alert('Please specify your other favorite food.');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});