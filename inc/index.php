<?php
$public_config = new stdClass;
$public_config->public_log_path = $config->public_log_path;


?>
<!doctype html>
<html>
<head>
  <link href="https://fonts.googleapis.com/css2?family=Source+Code+Pro:ital,wght@0,500;0,700;1,500&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">
<style>
* {
  margin: 0;
  padding: 0;
  font-size: 14px;
  border: none;
  box-sizing: border-box;
}

body {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  width: 100%;
  background-color: #9aa3b0;
  max-height: 100vh;
  height: 100vh;
  padding: 20px;
  color: #2d3239;
  font-family: 'Poppins';
}

.controls {
  width: 300px;
  margin-right: 20px;
  padding: 20px;
  border: 1px solid #404751;
  background-color: #efefef;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
}

.build-info {
  margin-top: 20px;
}

.logfile {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

h4 {
  margin-bottom: 10px;
  font-size: 18px;
}

button {
  border: none;
  padding: 0.5em 1em;
  font-weight: 600;
  margin-right: 0.5em;
  font-size: 16px;
  position: relative;
  border-bottom: 3px solid;
  outline: none;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
  border-radius: 1px;
  background: #3db63d;
  color: #fff;
  border-color: #16831d;
  font-family: 'Poppins';
}

button:disabled, button:hover {
  top: 1px;
  border-width: 2px;
  margin-bottom: 1px;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}

button.kill {

  background: #dc4a42;
  border-color: #ae3433;
}

button:active {
  top: 3px;
  border-width: 0px;
  margin-bottom: 3px;
  box-shadow: none;
}

.output-wrapper {
  width: 800px;
  height: 100%;
  margin-left: 20px;
  padding: 20px;
  border: 1px solid #404751;
  background-color: #efefef;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

#output {
  display: block;
  border: 1px solid #404751;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.2);
  background-color: #404751;
  padding: 5px;
  color: #e7e7e7;
  overflow-y: scroll;
  height: 100%;
  font-family: 'Source Code Pro', monospace;
}

.row {
  white-space: break-spaces;
}

.row.header>b {
  color: #3498db;
}

.row.header>b:nth-child(2) {
  color: #fff;
}

</style>
<script>
const config = <?= json_encode($public_config) ?>;
</script>
</head>
<body>
<div class="controls">
  <button class="tertiary" id="build-trigger">Trigger build</button>
  <div class="build-info">
    <h4>Current build</h4>
    <div class="">PID: <b id="build-pid"></b></div> 
    <div class="logfile">Log file: <b id="build-logfile"></b></div> 
    <div class="">Running: <b id="build-status"></b></div> 
  </div>

</div>
  <div class="output-wrapper">
  <h4>Log output</h4>
  <pre id="output"></pre>
<script>
const actionButton = document.getElementById('build-trigger');
const pidElement = document.getElementById('build-pid');
const logfileElement = document.getElementById('build-logfile');
const statusElement = document.getElementById('build-status');
const outputElement = document.getElementById('output');

let token = null;
let running = false;

function getLogPath(logfile) { return `${config.public_log_path}/${logfile}` }

async function triggerBuild() {
  actionButton.classList.toggle('kill', true);
  actionButton.innerText = 'Kill build';

  running = true;
  token = await fetch('?build').then(res => res.text());

  const { pid, logfile } = JSON.parse(token).payload;

  pidElement.innerText = pid
  logfileElement.innerHTML = `<a href="${getLogPath(logfile)}" target="_blank">${logfile}</a>`

  poll()
}

async function killBuild() {
  if (token) {
    await fetch(`?kill&token=${token}`)
    poll()
  }
}


actionButton.addEventListener('click', () => {
  if (running) { killBuild() } else { triggerBuild() }
});


const HEADER_REGEX = /^(=+)( .+ )(=+)/

function renderOutput(output) {
  outputElement.innerText = ''

  output.split('\n').forEach(line => {
    const row = document.createElement('div');
    row.classList.toggle('row', true)


    const headerMatch = line.match(HEADER_REGEX)
    if (headerMatch) {
      row.classList.toggle('header', true)

      headerMatch.slice(1, 4).forEach(match => {
        const elem = document.createElement('b')
        elem.innerText = match
        row.appendChild(elem)
        outputElement.appendChild(row)
      })

      return
    }

    const elem = document.createTextNode(line)
    row.appendChild(elem)
    outputElement.appendChild(row)
  })
}

async function poll() {
  if (token) {
    const { logfile } = JSON.parse(token).payload;

    const status = await fetch(`?status&token=${token}`).then(res => res.json())
    running = status.running;
    actionButton.classList.toggle('kill', running);
    actionButton.innerText = running ? 'Kill build' : 'Trigger build';

    statusElement.innerText = status.running;

    renderOutput(await fetch(getLogPath(logfile)).then(res => res.text()))

    if (running) {
      setTimeout(poll, 500);
    }
  }
}

</script>
</body>
</html>