<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Form</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>_Surveys</h2>
            <nav>
                <ul>
                    <li><a href="survey.php">FILL OUT SURVEY</a></li>
                    <li><a href="results.php">VIEW SURVEY RESULTS</a></li>
                </ul>
            </nav>
        </div>

        <form id="surveyForm" action="submit.php" method="POST">
            <h3>Personal Details :</h3>
            
            <div class="form-group">
                <label for="full_name">Full Names:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth:</label>
                <input type="date" id="dob" name="dob" required>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="tel" id="contact_number" name="contact_number" required>
            </div><br><br>
            
            <h2>What is your favorite food?</h2>
            <div class="checkbox-group">
                <label><input type="checkbox" name="food[]" value="pizza"> Pizza</label>
                <label><input type="checkbox" name="food[]" value="pasta"> Pasta</label>
                <label><input type="checkbox" name="food[]" value="pap_wors"> Pap and Wors</label>
                <label>
                    <input type="checkbox" id="other_checkbox" name="food[]" value="other"> Other:
                    <input type="text" id="other_food" name="other_food" placeholder="Please specify" disabled>
                </label>
            </div>
            
            <h2>Please rate your level of agreement on a scale from 1 to 5, with 1 being "strongly agree" and 5 being "strongly disagree."</h2>
            <table class="rating-table">
                <tr>
                    <th></th>
                    <th>Strongly Agree</th>
                    <th>Agree</th>
                    <th>Neutral</th>
                    <th>Disagree</th>
                    <th>Strongly Disagree</th>
                </tr>
                <tr>
                    <td>I like to watch movies</td>
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <td><input type="radio" name="movies_rating" value="<?= $i ?>" required></td>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <td>I like to listen to radio</td>
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <td><input type="radio" name="radio_rating" value="<?= $i ?>" required></td>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <td>I like to eat out</td>
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <td><input type="radio" name="eat_out_rating" value="<?= $i ?>" required></td>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <td>I like to watch TV</td>
                    <?php for ($i=1; $i<=5; $i++): ?>
                        <td><input type="radio" name="tv_rating" value="<?= $i ?>" required></td>
                    <?php endfor; ?>
                </tr>
            </table>

            <div class="button-container">
                <button type="submit" class="submit-btn">SUBMIT</button>
            </div>
        </form>
    </div>

    <script>
        // Enable/disable "Other" text input based on checkbox
        document.getElementById('other_checkbox').addEventListener('change', function() {
            const otherFoodInput = document.getElementById('other_food');
            otherFoodInput.disabled = !this.checked;
            if (!this.checked) {
                otherFoodInput.value = "";
            }
        });
    </script>
</body>
</html>
<?php