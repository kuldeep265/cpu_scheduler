// Process class to store process information
class Process {
    constructor(id, arrivalTime, burstTime, priority = 0) {
        this.id = id;
        this.arrivalTime = parseInt(arrivalTime);
        this.burstTime = parseInt(burstTime);
        this.priority = parseInt(priority);
        this.remainingTime = this.burstTime;
        this.completionTime = 0;
        this.waitingTime = 0;
        this.turnaroundTime = 0;
    }
}

// Scheduler class to handle different scheduling algorithms
class Scheduler {
    constructor() {
        this.processes = [];
        this.timeline = [];
        this.currentTime = 0;
        this.timeQuantum = 2;
    }

    setTimeQuantum(quantum) {
        this.timeQuantum = parseInt(quantum);
    }

    addProcess(process) {
        this.processes.push(process);
    }

    clearProcesses() {
        this.processes = [];
        this.timeline = [];
        this.currentTime = 0;
    }

    updateProcessMetrics(process) {
        process.turnaroundTime = process.completionTime - process.arrivalTime;
        process.waitingTime = process.turnaroundTime - process.burstTime;
    }

    addTimelineEntry(id, start, end) {
        this.timeline.push({ id, start, end });
        this.currentTime = end;
    }

    handleIdle(nextTime) {
        if (this.currentTime < nextTime) {
            this.addTimelineEntry('idle', this.currentTime, nextTime);
        }
    }

    // First Come First Serve (FCFS)
    fcfs() {
        this.processes.sort((a, b) => a.arrivalTime - b.arrivalTime);
        this.currentTime = 0;
        this.timeline = [];

        this.processes.forEach(process => {
            this.handleIdle(process.arrivalTime);
            this.addTimelineEntry(process.id, this.currentTime, this.currentTime + process.burstTime);
            process.completionTime = this.currentTime;
            this.updateProcessMetrics(process);
        });

        return this.calculateMetrics();
    }

    // Shortest Job First (SJF)
    sjf() {
        let completed = new Set();
        this.currentTime = 0;
        this.timeline = [];
        
        while (completed.size < this.processes.length) {
            const available = this.processes
                .filter(p => p.arrivalTime <= this.currentTime && !completed.has(p.id))
                .sort((a, b) => a.burstTime - b.burstTime);

            if (available.length === 0) {
                const nextArrival = Math.min(...this.processes
                    .filter(p => !completed.has(p.id))
                    .map(p => p.arrivalTime));
                this.handleIdle(nextArrival);
                continue;
            }

            const process = available[0];
            this.addTimelineEntry(process.id, this.currentTime, this.currentTime + process.burstTime);
            process.completionTime = this.currentTime;
            this.updateProcessMetrics(process);
            completed.add(process.id);
        }

        return this.calculateMetrics();
    }

    // Round Robin
    roundRobin() {
        let remaining = this.processes.map(p => ({...p}));
        this.currentTime = 0;
        this.timeline = [];

        while (remaining.length > 0) {
            let executed = false;

            for (let i = 0; i < remaining.length; i++) {
                const process = remaining[i];
                if (process.arrivalTime <= this.currentTime) {
                    executed = true;
                    const executeTime = Math.min(this.timeQuantum, process.remainingTime);
                    this.addTimelineEntry(process.id, this.currentTime, this.currentTime + executeTime);
                    process.remainingTime -= executeTime;

                    if (process.remainingTime === 0) {
                        const original = this.processes.find(p => p.id === process.id);
                        original.completionTime = this.currentTime;
                        this.updateProcessMetrics(original);
                        remaining.splice(i--, 1);
                    }
                }
            }

            if (!executed) {
                const nextArrival = Math.min(...remaining.map(p => p.arrivalTime));
                this.handleIdle(nextArrival);
            }
        }

        return this.calculateMetrics();
    }

    // Priority Scheduling
    priorityScheduling() {
        let completed = new Set();
        this.currentTime = 0;
        this.timeline = [];
        let remainingProcesses = [...this.processes];

        while (completed.size < this.processes.length) {
            let availableProcesses = remainingProcesses
                .filter(p => p.arrivalTime <= this.currentTime && !completed.has(p.id))
                .sort((a, b) => a.priority - b.priority);

            if (availableProcesses.length === 0) {
                let nextArrival = Math.min(...remainingProcesses
                    .filter(p => !completed.has(p.id))
                    .map(p => p.arrivalTime));
                
                if (this.currentTime < nextArrival) {
                    this.timeline.push({ id: 'idle', start: this.currentTime, end: nextArrival });
                    this.currentTime = nextArrival;
                }
                continue;
            }

            let process = availableProcesses[0];
            process.startTime = this.currentTime;
            process.completionTime = this.currentTime + process.burstTime;
            process.turnaroundTime = process.completionTime - process.arrivalTime;
            process.waitingTime = process.turnaroundTime - process.burstTime;

            this.timeline.push({
                id: process.id,
                start: this.currentTime,
                end: process.completionTime
            });

            this.currentTime = process.completionTime;
            completed.add(process.id);
        }

        return this.calculateMetrics();
    }

    calculateMetrics() {
        const totalProcesses = this.processes.length;
        const avgWaitingTime = this.processes.reduce((sum, p) => sum + p.waitingTime, 0) / totalProcesses;
        const avgTurnaroundTime = this.processes.reduce((sum, p) => sum + p.turnaroundTime, 0) / totalProcesses;
        const totalTime = this.currentTime;
        const idleTime = this.timeline
            .filter(t => t.id === 'idle')
            .reduce((sum, t) => sum + (t.end - t.start), 0);
        const cpuUtilization = ((totalTime - idleTime) / totalTime) * 100;
        const throughput = totalProcesses / totalTime;

        return {
            avgWaitingTime,
            avgTurnaroundTime,
            cpuUtilization,
            throughput
        };
    }
}

// UI Controller
class UIController {
    constructor() {
        this.scheduler = new Scheduler();
        this.processCounter = 1;
        this.setupEventListeners();
        this.processColors = ['#ff69b4', '#4169e1', '#ffd700', '#98fb98'];
    }

    setupEventListeners() {
        document.getElementById('algorithm').addEventListener('change', this.handleAlgorithmChange.bind(this));
        document.getElementById('addProcess').addEventListener('click', this.addProcessToUI.bind(this));
        document.getElementById('clearProcesses').addEventListener('click', this.clearProcesses.bind(this));
        document.getElementById('submitBtn').addEventListener('click', this.runSimulation.bind(this));
    }

    handleAlgorithmChange(e) {
        const timeQuantumSection = document.getElementById('timeQuantumSection');
        timeQuantumSection.style.display = e.target.value === 'rr' ? 'block' : 'none';

        // Update existing processes to show/hide priority input
        const processes = document.querySelectorAll('.process');
        processes.forEach(process => {
            let existingPriority = process.querySelector('.priority-container');
            
            if (e.target.value === 'priority') {
                if (!existingPriority) {
                    const priorityDiv = document.createElement('div');
                    priorityDiv.className = 'priority-container';
                    priorityDiv.innerHTML = `
                        <label>Priority:</label>
                        <input type="number" class="priority" min="1" value="1">
                    `;
                    process.appendChild(priorityDiv);
                }
            } else {
                if (existingPriority) {
                    existingPriority.remove();
                }
            }
        });
    }

    addProcessToUI() {
        const processList = document.getElementById('processList');
        const processDiv = document.createElement('div');
        processDiv.className = 'process';
        processDiv.style.backgroundColor = this.processColors[(this.processCounter - 1) % this.processColors.length];
        
        const algorithm = document.getElementById('algorithm').value;
        const showPriority = algorithm === 'priority';
        
        processDiv.innerHTML = `
            <div>
                <label>Arrival Time:</label>
                <input type="number" class="arrival-time" min="0" value="0">
            </div>
            <div>
                <label>Burst Time:</label>
                <input type="number" class="burst-time" min="1" value="1">
            </div>
            ${showPriority ? `
            <div>
                <label>Priority:</label>
                <input type="number" class="priority" min="1" value="1">
            </div>
            ` : ''}
        `;

        processList.appendChild(processDiv);
        this.processCounter++;
    }

    clearProcesses() {
        document.getElementById('processList').innerHTML = '';
        document.getElementById('ganttChart').innerHTML = '';
        document.getElementById('timeline').innerHTML = '';
        document.getElementById('resultsTableBody').innerHTML = '';
        this.resetMetrics();
        this.processCounter = 1;
        this.scheduler.clearProcesses();
    }

    resetMetrics() {
        document.getElementById('avgWaitingTime').textContent = '0.00';
        document.getElementById('avgTurnaroundTime').textContent = '0.00';
        document.getElementById('throughput').textContent = '0.00';
        document.getElementById('cpuUtilization').textContent = '0.00%';
    }

    runSimulation() {
        this.scheduler.clearProcesses();
        const algorithm = document.getElementById('algorithm').value;
        const timeQuantum = parseInt(document.getElementById('timeQuantum').value);
        
        // Gather processes from UI
        const processElements = document.querySelectorAll('.process');
        processElements.forEach((elem, index) => {
            const arrivalTime = parseInt(elem.querySelector('.arrival-time').value);
            const burstTime = parseInt(elem.querySelector('.burst-time').value);
            const priority = elem.querySelector('.priority') ? 
                parseInt(elem.querySelector('.priority').value) : 0;
            
            const process = new Process(index + 1, arrivalTime, burstTime, priority);
            this.scheduler.addProcess(process);
        });

        if (algorithm === 'rr') {
            this.scheduler.setTimeQuantum(timeQuantum);
        }

        // Run selected algorithm
        let metrics;
        switch(algorithm) {
            case 'fcfs':
                metrics = this.scheduler.fcfs();
                break;
            case 'sjf':
                metrics = this.scheduler.sjf();
                break;
            case 'rr':
                metrics = this.scheduler.roundRobin();
                break;
            case 'priority':
                metrics = this.scheduler.priorityScheduling();
                break;
        }

        this.updateUI(metrics);
    }

    updateUI(metrics) {
        // Update Gantt Chart
        const ganttChart = document.getElementById('ganttChart');
        const timeline = document.getElementById('timeline');
        ganttChart.innerHTML = '';
        timeline.innerHTML = '';

        const totalTime = this.scheduler.currentTime;
        const timelineWidth = ganttChart.offsetWidth - 40; // Account for padding
        const scale = timelineWidth / totalTime;

        // Create a container for the Gantt chart blocks and timing
        const ganttContainer = document.createElement('div');
        ganttContainer.className = 'gantt-container';
        ganttChart.appendChild(ganttContainer);

        // Add idle time row
        const idleRow = document.createElement('div');
        idleRow.className = 'process-row';
        idleRow.innerHTML = '<div class="process-label">Idle</div>';
        
        const idleTimeline = document.createElement('div');
        idleTimeline.className = 'timeline-row';
        
        // Find all idle periods
        const idlePeriods = this.scheduler.timeline
            .filter(item => item.id === 'idle')
            .sort((a, b) => a.start - b.start);

        idlePeriods.forEach(item => {
            const block = document.createElement('div');
            block.className = 'gantt-block idle-block';
            block.style.left = `${item.start * scale}px`;
            block.style.width = `${(item.end - item.start) * scale}px`;
            block.textContent = `${item.start}-${item.end}`;
            idleTimeline.appendChild(block);
        });

        idleRow.appendChild(idleTimeline);
        ganttContainer.appendChild(idleRow);

        // Create a row for each process
        this.scheduler.processes.forEach(process => {
            const processRow = document.createElement('div');
            processRow.className = 'process-row';
            processRow.innerHTML = `<div class="process-label">P${process.id}</div>`;
            
            const timelineRow = document.createElement('div');
            timelineRow.className = 'timeline-row';
            
            // Find all timeline entries for this process
            const processTimeline = this.scheduler.timeline
                .filter(item => item.id === process.id)
                .sort((a, b) => a.start - b.start);

            // Create blocks for each execution period
            processTimeline.forEach(item => {
                const block = document.createElement('div');
                block.className = 'gantt-block';
                block.style.left = `${item.start * scale}px`;
                block.style.width = `${(item.end - item.start) * scale}px`;
                block.style.backgroundColor = this.processColors[(process.id - 1) % this.processColors.length];
                block.textContent = `${item.start}-${item.end}`;
                timelineRow.appendChild(block);
            });

            processRow.appendChild(timelineRow);
            ganttContainer.appendChild(processRow);
        });

        // Add time markers at the bottom
        const timeMarkers = document.createElement('div');
        timeMarkers.className = 'time-markers';
        
        // Add markers every 2 units of time
        for (let time = 0; time <= totalTime; time += 2) {
            const marker = document.createElement('div');
            marker.className = 'time-marker';
            marker.style.left = `${time * scale}px`;
            marker.innerHTML = `
                <div class="marker-line"></div>
                <div class="marker-label">${time}</div>
            `;
            timeMarkers.appendChild(marker);
        }
        
        ganttContainer.appendChild(timeMarkers);

        // Update Results Table
        const resultsTableBody = document.getElementById('resultsTableBody');
        resultsTableBody.innerHTML = '';
        
        this.scheduler.processes.forEach(process => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>P${process.id}</td>
                <td>${process.arrivalTime}</td>
                <td>${process.burstTime}</td>
                <td>${process.completionTime}</td>
                <td>${process.waitingTime}</td>
                <td>${process.turnaroundTime}</td>
            `;
            resultsTableBody.appendChild(row);
        });

        // Update Metrics
        document.getElementById('avgWaitingTime').textContent = metrics.avgWaitingTime.toFixed(2);
        document.getElementById('avgTurnaroundTime').textContent = metrics.avgTurnaroundTime.toFixed(2);
        document.getElementById('throughput').textContent = metrics.throughput.toFixed(2);
        document.getElementById('cpuUtilization').textContent = `${metrics.cpuUtilization.toFixed(2)}%`;
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => new UIController()); 