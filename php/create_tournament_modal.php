<div id="modal-create" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Create New Tournament</h3>
        <form id="create-tournament-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="create_tournament" value="1">
            <div class="form-group">
                <label for="new-tournament-name">Tournament Name:</label>
                <input type="text" id="new-tournament-name" name="tournament_name" required>
            </div>
            <div class="form-group">
                <label for="new-tournament-date">Tournament Date:</label>
                <input type="date" id="new-tournament-date" name="tournament_date" required>
            </div>
            <div class="form-group">
                <label for="new-tournament-game">Game:</label>
                <select id="new-tournament-game" name="tournament_game" required>
                    <option value="eafc24">EA FC 24</option>
                    <option value="tekken8">Tekken 8</option>
                    <option value="efootball">eFootball</option>
                </select>
            </div>
            <div class="form-group">
                <label for="new-tournament-venue">Venue:</label>
                <input type="text" id="new-tournament-venue" name="tournament_venue" required>
            </div>
            <div class="form-group">
                <label for="new-tournament-format">Format:</label>
                <select id="new-tournament-format" name="tournament_format" required>
                    <option value="single_elimination">Single Elimination</option>
                    <option value="double_elimination">Double Elimination</option>
                </select>
            </div>
            <div class="form-group">
                <label for="new-total-participant">Total Participants:</label>
                <select id="new-total-participant" name="total_participant" required>
                    <option value="8">8</option>
                    <option value="16">16</option>
                    <option value="32">32</option>
                    <option value="64">64</option>
                </select>
            </div>
            <div class="form-group">
                <label for="new-tournament-poster">Poster:</label>
                <input type="file" id="new-tournament-poster" name="tournament_poster" accept="image/*" onchange="previewPoster(event)">
                <img id="poster-preview" src="" alt="Poster Preview" style="display:none; margin-top:10px; max-width:100%;">
            </div>
            <div class="form-group">
                <label for="new-tournament-description">Description:</label>
                <textarea id="new-tournament-description" name="tournament_description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="new-registration-fee">Registration Fee:</label>
                <select id="new-registration-fee" name="registration_fee" required>
                    <option value="free">Free</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="form-group" id="new-fee-group" style="display: none;">
                <label for="new-registration-price">Price (RM):</label>
                <input type="number" id="new-registration-price" name="registration_price" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label for="new-start-time">Start Time:</label>
                <input type="time" id="new-start-time" name="start_time" required>
            </div>
            <button type="submit">Create Tournament</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('new-tournament-date').setAttribute('min', today);
    });

    function previewPoster(event) {
        const posterPreview = document.getElementById('poster-preview');
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                posterPreview.src = e.target.result;
                posterPreview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            posterPreview.style.display = 'none';
        }
    }
</script>
