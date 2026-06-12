<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const currentStep = ref(1);
const standings = ref([]);
const fixtures = ref({});
const predictions = ref({});
const isLoading = ref(false);
const newTeamName = ref('');
const notification = ref({ show: false, message: '', type: 'success' });

const editingMatch = ref(null);
const editForm = ref({ home: 0, away: 0 });

const showNotification = (message, type = 'success') => {
    notification.value = { show: true, message, type };
    setTimeout(() => { notification.value.show = false; }, 3000);
};

const fetchSimulationData = async () => {
    isLoading.value = true;
    try {
        const [standingsRes, fixturesRes, predictionsRes] = await Promise.all([
            axios.get('/api/simulation/standings'),
            axios.get('/api/simulation/fixtures'),
            axios.get('/api/simulation/predictions')
        ]);
        standings.value = standingsRes.data;
        fixtures.value = fixturesRes.data;
        predictions.value = predictionsRes.data;
        
        if (Object.keys(fixtures.value).length > 0 && currentStep.value === 1) {
            currentStep.value = 3; 
        }
    } catch (error) {
        showNotification('Veriler yüklenirken sunucu hatası oluştu.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const addNewTeam = async () => {
    if (!newTeamName.value.trim()) return;
    isLoading.value = true;
    try {
        const res = await axios.post('/api/simulation/add-team', { name: newTeamName.value });
        showNotification(res.data.message, 'success');
        newTeamName.value = '';
        await fetchSimulationData();
    } catch (error) {
        showNotification(error.response?.data?.message || 'Error adding team.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const removeTeam = async (id) => {
    isLoading.value = true;
    try {
        const res = await axios.delete(`/api/simulation/remove-team/${id}`);
        showNotification(res.data.message, 'success');
        await fetchSimulationData();
    } catch (error) {
        showNotification('Error removing team.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const triggerAction = async (endpoint) => {
    isLoading.value = true;
    try {
        const res = await axios.post(`/api/simulation/${endpoint}`);
        await fetchSimulationData();
        
        if (endpoint === 'generate-fixtures') {
            currentStep.value = 2; 
            showNotification('Fixtures generated successfully.');
        } else if (endpoint === 'reset') {
            currentStep.value = 1; 
            showNotification('Simulation reset successfully.');
        } else {
            showNotification(res.data.message || 'Action completed.');
        }
    } catch (error) {
        showNotification('An error occurred.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const startEdit = (match) => {
    editingMatch.value = match.id;
    editForm.value.home = match.home_goals || 0;
    editForm.value.away = match.away_goals || 0;
};

const saveScore = async (matchId) => {
    isLoading.value = true;
    try {
        const res = await axios.post(`/api/simulation/update-score/${matchId}`, {
            home_goals: editForm.value.home,
            away_goals: editForm.value.away
        });
        editingMatch.value = null; 
        showNotification(res.data.message, 'success');
        await fetchSimulationData(); 
    } catch (error) {
        showNotification('Error updating score.', 'error');
    } finally {
        isLoading.value = false;
    }
};

const isTournamentFinished = computed(() => {
    const weeks = Object.keys(fixtures.value);
    if (weeks.length === 0) return false;
    for (const week of weeks) { 
        if (fixtures.value[week].some(m => !m.is_played)) return false; 
    }
    return true; 
});

const championTeam = computed(() => {
    return isTournamentFinished.value && standings.value.length > 0 ? standings.value[0] : null;
});

const currentWeekResult = computed(() => {
    const weeks = Object.keys(fixtures.value);
    if (weeks.length === 0) return null;
    
    let lastPlayedWeek = null;
    let nextUnplayedWeek = null;
    
    for (const week of weeks) {
        const isPlayed = fixtures.value[week].some(m => m.is_played);
        if (isPlayed) lastPlayedWeek = week;
        else if (!nextUnplayedWeek) nextUnplayedWeek = week;
    }
    
    const targetWeek = lastPlayedWeek || nextUnplayedWeek || weeks[0];
    return { week: targetWeek, matches: fixtures.value[targetWeek] };
});

onMounted(() => {
    fetchSimulationData();
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 p-8 font-sans">
        
        <transition name="fade">
            <div v-if="notification.show" 
                 :class="notification.type === 'success' ? 'bg-green-600' : 'bg-red-500'" 
                 class="fixed top-5 right-5 text-white px-6 py-3 rounded shadow-lg z-50">
                <span class="font-medium">{{ notification.message }}</span>
            </div>
        </transition>

        <div class="max-w-6xl mx-auto">
            <transition name="slide-fade" mode="out-in">
                
                <div v-if="currentStep === 1" class="bg-white p-10 rounded-lg shadow-sm">
                    <h1 class="text-3xl font-bold text-gray-800 mb-8">Tournament Teams</h1>
                    
                    <div class="mb-6 border rounded overflow-hidden">
                        <div class="bg-gray-800 text-white font-bold py-3 px-4 flex justify-between">
                            <span>Team Name</span>
                            <span class="font-normal text-sm text-gray-300">Total Teams: {{ standings.length }}</span>
                        </div>
                        <div v-for="s in standings" :key="s.team.id" class="border-b last:border-0 py-3 px-4 flex justify-between items-center group hover:bg-gray-50">
                            <span class="font-medium text-gray-700">{{ s.team.name }}</span>
                            <button @click="removeTeam(s.team.id)" :disabled="isLoading" class="text-red-400 hover:text-red-600 font-bold px-3 py-1 rounded transition opacity-0 group-hover:opacity-100 focus:opacity-100" title="Remove Team">✕</button>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 mb-8">
                        <input v-model="newTeamName" @keyup.enter="addNewTeam" type="text" placeholder="Enter new team name..." class="flex-1 border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                        <button @click="addNewTeam" :disabled="isLoading || !newTeamName" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2 rounded-md font-medium transition disabled:opacity-50">Add Team</button>
                    </div>

                    <button @click="triggerAction('generate-fixtures')" :disabled="isLoading || standings.length < 2" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-md font-medium shadow transition disabled:opacity-50">
                        Generate Fixtures
                    </button>
                </div>

                <div v-else-if="currentStep === 2" class="bg-white p-10 rounded-lg shadow-sm">
                    <h1 class="text-3xl font-bold text-gray-800 mb-8 border-b pb-4">Generated Fixtures</h1>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div v-for="(matches, week) in fixtures" :key="week" class="bg-gray-50 border rounded-lg overflow-hidden">
                            <div class="bg-gray-800 text-white font-bold py-2 px-4">Week {{ week }}</div>
                            <div class="p-4 space-y-3">
                                <div v-for="m in matches" :key="m.id" class="flex justify-between text-sm">
                                    <span class="w-2/5 text-right font-medium text-gray-700">{{ m.home_team.name }}</span>
                                    <span class="text-gray-400">-</span>
                                    <span class="w-2/5 font-medium text-gray-700">{{ m.away_team.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button @click="currentStep = 3" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-md font-medium shadow transition">
                        Start Simulation
                    </button>
                </div>

                <div v-else-if="currentStep === 3" class="space-y-8">
                    <h1 class="text-3xl font-bold text-gray-800 border-b pb-4">Simulation</h1>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-800 text-white">
                                    <tr>
                                        <th class="py-3 px-4 text-left">Team</th>
                                        <th>P</th><th>W</th><th>D</th><th>L</th><th>GD</th>
                                        <th class="text-teal-400 font-bold px-4">PTS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="s in standings" :key="s.id" class="border-b hover:bg-gray-50 transition">
                                        <td class="py-3 px-4 text-left font-medium text-gray-800">{{ s.team.name }}</td>
                                        <td>{{ s.played }}</td>
                                        <td>{{ s.won }}</td>
                                        <td>{{ s.drawn }}</td>
                                        <td>{{ s.lost }}</td>
                                        <td :class="s.goal_difference > 0 ? 'text-green-600 font-medium' : (s.goal_difference < 0 ? 'text-red-500 font-medium' : 'text-gray-500')">
                                            {{ s.goal_difference > 0 ? '+' + s.goal_difference : s.goal_difference }}
                                        </td>
                                        <td class="px-4 font-black text-lg text-gray-900">{{ s.points }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                            <div class="bg-gray-800 text-white font-bold py-3 px-4">Week {{ currentWeekResult?.week }} Results</div>
                            <div class="p-4 space-y-4 flex-1">
                                <div v-for="match in currentWeekResult?.matches" :key="match.id" class="flex justify-between items-center text-sm py-1">
                                    
                                    <span class="w-[28%] text-right font-medium transition" :class="match.is_played && match.home_goals > match.away_goals ? 'text-green-600 font-bold' : 'text-gray-700'">
                                        {{ match.home_team.name }}
                                    </span>
                                    
                                    <div v-if="editingMatch === match.id" class="w-[44%] flex items-center justify-center gap-2">
                                        <input type="number" v-model="editForm.home" class="w-12 h-10 border-2 border-gray-300 text-center rounded-md text-lg font-bold text-gray-800 focus:border-teal-500 outline-none hide-arrows" min="0">
                                        <span class="text-gray-400 font-black">-</span>
                                        <input type="number" v-model="editForm.away" class="w-12 h-10 border-2 border-gray-300 text-center rounded-md text-lg font-bold text-gray-800 focus:border-teal-500 outline-none hide-arrows" min="0">
                                        
                                        <div class="flex flex-col gap-1 ml-1">
                                            <button @click="saveScore(match.id)" class="bg-green-500 text-white rounded p-1 hover:bg-green-600 flex items-center justify-center" title="Save">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                            <button @click="editingMatch = null" class="bg-red-500 text-white rounded p-1 hover:bg-red-600 flex items-center justify-center" title="Cancel">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div v-else @click="match.is_played ? startEdit(match) : null" 
                                         class="w-[44%] text-center font-bold bg-gray-100 rounded py-2 transition-all mx-2" 
                                         :class="{'cursor-pointer hover:bg-teal-50 hover:text-teal-700 hover:shadow-inner ring-1 ring-gray-200': match.is_played}" 
                                         :title="match.is_played ? 'Click to edit score' : ''">
                                        {{ match.is_played ? `${match.home_goals} - ${match.away_goals}` : 'v' }}
                                    </div>

                                    <span class="w-[28%] text-left font-medium transition" :class="match.is_played && match.away_goals > match.home_goals ? 'text-green-600 font-bold' : 'text-gray-700'">
                                        {{ match.away_team.name }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-gray-50 text-center py-2 text-xs text-gray-500 italic border-t">
                                Click on played match scores to edit them.
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="bg-gray-800 text-white font-bold py-3 px-4 flex justify-between">
                                <span>Championship Predictions</span><span>%</span>
                            </div>
                            <div class="p-4 space-y-4">
                                <div v-if="Object.keys(predictions).length === 0" class="text-gray-400 text-center text-sm py-4 italic">
                                    Predictions will be active in the last 3 weeks...
                                </div>
                                <div v-else v-for="(p, name) in predictions" :key="name" class="flex justify-between items-center border-b border-gray-100 pb-2 last:border-0">
                                    <span class="font-medium text-gray-700">{{ name }}</span>
                                    <span class="font-black text-gray-900">{{ p }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <transition name="fade">
                        <div v-if="isTournamentFinished" class="bg-gradient-to-r from-yellow-300 via-yellow-400 to-yellow-300 border border-yellow-500 p-6 rounded-lg shadow-lg text-center mt-8">
                            <h2 class="font-black text-3xl text-yellow-900 tracking-wider">🏆 TOURNAMENT COMPLETED 🏆</h2>
                            <p class="text-xl text-yellow-800 mt-2 font-medium">Champion: <span class="font-black text-2xl uppercase underline decoration-2">{{ championTeam?.team.name }}</span>!</p>
                        </div>
                    </transition>

                    <div class="flex justify-center gap-6 mt-8">
                        <button v-if="!isTournamentFinished" @click="triggerAction('play-all')" :disabled="isLoading" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-md font-bold shadow transition disabled:opacity-50">Play All Weeks</button>
                        <button v-if="!isTournamentFinished" @click="triggerAction('play-next-week')" :disabled="isLoading" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-md font-bold shadow transition disabled:opacity-50">Play Next Week</button>
                        <button @click="triggerAction('reset')" :disabled="isLoading" :class="isTournamentFinished ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-red-600 hover:bg-red-700'" class="text-white px-10 py-3 rounded-md font-bold shadow transition disabled:opacity-50">{{ isTournamentFinished ? '🔄 Play Again' : 'Reset Data' }}</button>
                    </div>

                </div>
            </transition>
        </div>
    </div>
</template>

<style>
.hide-arrows::-webkit-outer-spin-button,
.hide-arrows::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.hide-arrows {
  -moz-appearance: textfield;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-fade-enter-active { transition: all 0.4s ease-out; }
.slide-fade-leave-active { transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1); }
.slide-fade-enter-from { transform: translateX(20px); opacity: 0; }
.slide-fade-leave-to { transform: translateX(-20px); opacity: 0; }
</style>