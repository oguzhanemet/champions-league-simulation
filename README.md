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
Install Composer Dependencies:

Bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php8.2-composer:latest \
    composer install --ignore-platform-reqs
Setup Environment:

Bash
cp .env.example .env
Start Laravel Sail (Docker):

Bash
./vendor/bin/sail up -d
Generate App Key & Migrate Database:

Bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
Install NPM Packages & Build:

Bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
The application is now live at: http://localhost

🧪 Automated Unit Testing
The project includes automated feature tests to ensure the integrity of the simulation, fixture generation, and dynamic team management.

To run the test suite:

Bash
./vendor/bin/sail artisan test

Developed as a technical showcase by Oğuzhan Emet.