# 🏆 Champions League Simulation

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

A full-stack web application that simulates a Champions League group stage tournament. Built with **Laravel** and **Vue 3 (Composition API)**, this project not only meets the core requirements but extends them with advanced statistical algorithms, dynamic architectures, and interactive UI features.

## ✨ Standout Features & "Extras" Implemented

This project goes beyond a simple random number generator. It is engineered with scalability and realism in mind:

* 🎯 **Poisson Distribution Match Engine:** Match results are not purely random. The simulation uses Poisson distribution mathematics factoring in `attack_strength`, `defense_strength`, and a `10% Home Advantage` multiplier. A weaker team *can* upset a stronger team, but statistically, the odds reflect real-world football dynamics.
* 🔮 **Monte Carlo Championship Predictions:** Championship odds (triggered in the final 3 weeks) are calculated by simulating the remaining fixtures **10,000 times** in the background. It accounts for current points, remaining matches, and goal differences to provide highly accurate, probabilistic forecasting.
* ♾️ **Dynamic "N-Team" Architecture:** *“Team count should not break the fixtures.”* The Round-Robin fixture generation algorithm is fully dynamic. You can add 5, 6, or 10 teams. The system automatically calculates the required weeks and gracefully handles "Bye" weeks for odd numbers of teams without breaking.
* ✏️ **Live Score Editing (Bonus Requirement):** Users can click on any played match score to edit it inline. The backend automatically catches this, purges the current standings, and cleanly recalculates the entire league table from scratch based on the new truths.
* 🤖 **Play All Automation (Bonus Requirement):** A single click simulates all remaining fixtures instantly, updates the league table, and calculates the final champion.
* 🔄 **Full CRUD & State Management:** Add new teams on the fly or remove existing ones. The UI reacts instantly without page reloads.

## 🏗️ Technical Architecture

* **Backend:** Laravel (RESTful API, Service Repository Pattern). Complex logic is decoupled into dedicated services (`FixtureService`, `MatchSimulationService`, `ChampionshipPredictionService`).
* **Frontend:** Vue 3 `<script setup>`, Axios for API calls, completely reactive state management, and Tailwind CSS for a responsive, polished UI.
* **Database:** Relational schema handling Teams, Fixtures, and Standings.

## 🚀 Getting Started

This project is fully Dockerized using Laravel Sail for a zero-headache setup.

### Prerequisites
* Docker & Docker Desktop
* Composer

### Installation

1. **Clone the repository:**
```bash
git clone [https://github.com/oguzhanemet/champions-league-simulation.git](https://github.com/oguzhanemet/champions-league-simulation.git)
cd champions-league-simulation
```

2. **Install Composer Dependencies:**
*(Using an isolated Docker container to ensure PHP 8.4 compatibility)*
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

3. **Setup Environment & Database Credentials:**
*(This block automatically creates your .env file and injects the default Laravel Sail database credentials)*
```bash
cp .env.example .env
echo "DB_CONNECTION=mysql" >> .env
echo "DB_HOST=mysql" >> .env
echo "DB_PORT=3306" >> .env
echo "DB_DATABASE=champions_league" >> .env
echo "DB_USERNAME=sail" >> .env
echo "DB_PASSWORD=password" >> .env
```

4. **Start Laravel Sail (Docker):**
```bash
./vendor/bin/sail up -d
```

5. **Generate App Key & Migrate Database:**
*(Note: If you receive a "Connection refused" error on migration, simply wait 5 seconds for MySQL to fully initialize and run the command again.)*
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
```

6. **Install NPM Packages & Build:**
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

**The application is now live at:** `http://localhost`

---

### 🛠️ Troubleshooting: Port Conflicts

If you have local services (like MySQL, Redis, or another web server) running in the background, Docker might throw a **"port is already allocated"** error when running `./vendor/bin/sail up -d`. 

You can easily bypass these conflicts by assigning alternative ports. Run the relevant commands below to update your `.env` file, then restart Sail (`./vendor/bin/sail down` and `./vendor/bin/sail up -d`):

**If Web Port (80) is occupied:**
```bash
echo "APP_PORT=8000" >> .env
```

**If MySQL Port (3306) is occupied:**
```bash
echo "FORWARD_DB_PORT=3307" >> .env
```

**If Redis Port (6379) is occupied:**
```bash
echo "FORWARD_REDIS_PORT=6380" >> .env
```

**If Vite/Vue Port (5173) is occupied:**
```bash
echo "VITE_PORT=5174" >> .env
```

---

## 🧪 Automated Unit Testing
The project includes automated feature tests to ensure the integrity of the simulation, fixture generation, and dynamic team management.

To run the test suite:

```bash
./vendor/bin/sail artisan test
```

Developed as a technical showcase by Oğuzhan Emet.
