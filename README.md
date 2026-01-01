# Blank Slate - Web Edition

A real-time, mobile-responsive web version of the popular party game Blank Slate.

## 🚀 Installation

1. **Prerequisites**: You need a web server with **PHP 7.4+** and **MySQL/MariaDB**. (e.g., XAMPP, WAMP, or a standard hosting provider).
2. **Database Setup**:
   - Create a database named `blank_slate`.
   - Import the provided `setup.sql` file into your database tool (like phpMyAdmin).
3. **Configuration**:
   - Open `db.php` and update the `$user`, `$pass`, and `$dbname` to match your server credentials.
4. **Upload**: Move all files into a folder on your web server.

## 🎮 How to Play

### For Players
1. Navigate to the game URL.
2. Enter your name on the registration page (`index.php`).
3. You will be redirected to the game room (`user.php`).
4. Wait for the Game Master to start a round.
5. Fill in the blank! Match exactly 1 other person for **3 points**, or 2+ people for **1 point**.

### For the Game Master
1. Access `admin.php`.
2. Enter the "Left" or "Right" prompt (e.g., "SUN ____" or "____ BOARD").
3. Click **Start Round** to send the prompt to all players.
4. Once everyone has submitted, click **Lock & Score**.
5. Click **Next Round** to clear the board for a new prompt.

## 🛠 Features
- **Real-time Syncing**: No page refreshes required.
- **Points Logic**: Automatically calculates scores based on matching words.
- **History Table**: View detailed results of previous rounds.
- **Dark Mode**: High-contrast mode for night gaming.
- **Game Rules**: Toggle "Allow Spaces" in the admin menu.