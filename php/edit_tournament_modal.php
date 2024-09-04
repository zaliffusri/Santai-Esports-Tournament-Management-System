<div id="modal-edit" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Edit Tournament</h3>
        <form id="edit-tournament-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" id="tournament-id" name="tournament_id">
            <div class="form-group">
                <label for="tournament-name">Tournament Name:</label>
                <input type="text" id="tournament-name" name="tournament_name" required>
            </div>
            <div class="form-group">
                <label for="tournament-date">Tournament Date:</label>
                <input type="date" id="tournament-date" name="tournament_date" required disabled>
            </div>
            <div class="form-group">
                <label for="tournament-game">Game:</label>
                <select id="tournament-game" name="tournament_game" required>
                    <option value="eafc24">EA FC 24</option>
                    <option value="tekken8">Tekken 8</option>
                    <option value="efootball">eFootball</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tournament-venue">Venue:</label>
                <input type="text" id="tournament-venue" name="tournament_venue" required>
            </div>
            <div class="form-group">
                <label for="tournament-format">Format:</label>
                <select id="tournament-format" name="tournament_format" required disabled>
                    <option value="single_elimination">Single Elimination</option>
                    <option value="double_elimination">Double Elimination</option>
                </select>
            </div>
            <div class="form-group">
                <label for="total-participant">Total Participants:</label>
                <select id="total-participant" name="total_participant" required>
                    <option value="8">8</option>
                    <option value="16">16</option>
                    <option value="32">32</option>
                    <option value="64">64</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tournament-poster">Poster:</label>
                <input type="file" id="tournament-poster" name="tournament_poster" accept="image/*" onchange="previewPoster(event, 'edit')">
                <img id="edit-poster-preview" src="" alt="Poster Preview" style="display:none; margin-top:10px; max-width:100%;">
            </div>
            <div class="form-group">
                <label for="tournament-description">Description:</label>
                <textarea id="tournament-description" name="tournament_description" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="registration-fee">Registration Fee:</label>
                <select id="registration-fee" name="registration_fee" required disabled>
                    <option value="free">Free</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="form-group" id="fee-group" style="display: none;">
                <label for="registration-price">Price (RM):</label>
                <input type="number" id="registration-price" name="registration_price" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label for="start-time">Start Time:</label>
                <input type="time" id="start-time" name="start_time" required disabled>
            </div>
            <button type="submit" name="update_tournament">Update Tournament</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', (event) => {
                const buttonData = event.currentTarget.dataset;
                document.getElementById('tournament-id').value = buttonData.id;
                document.getElementById('tournament-name').value = buttonData.name;
                document.getElementById('tournament-date').value = buttonData.date;
                document.getElementById('tournament-game').value = buttonData.game;
                document.getElementById('tournament-venue').value = buttonData.venue;
                document.getElementById('tournament-format').value = buttonData.format;
                document.getElementById('total-participant').value = buttonData.participants;
                document.getElementById('tournament-description').value = buttonData.description;
                document.getElementById('registration-fee').value = buttonData.fee;
                document.getElementById('registration-price').value = buttonData.price;
                document.getElementById('start-time').value = buttonData.time;

                if (buttonData.fee === 'paid') {
                    document.getElementById('fee-group').style.display = 'block';
                } else {
                    document.getElementById('fee-group').style.display = 'none';
                }

                const posterPreview = document.getElementById('edit-poster-preview');
                if (buttonData.poster) {
                    posterPreview.src = buttonData.poster;
                    posterPreview.style.display = 'block';
                } else {
                    posterPreview.style.display = 'none';
                }

                document.getElementById('modal-edit').style.display = 'block';
            });
        });

        // Set minimum date for tournament date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tournament-date').setAttribute('min', today);
    });

    function previewPoster(event, mode = 'create') {
        const posterPreview = mode === 'create' ? document.getElementById('poster-preview') : document.getElementById('edit-poster-preview');
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
