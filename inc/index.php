<?php

$public_config = new stdClass;
$public_config->public_log_path = $config->public_log_path;

$token = "";
if (file_exists($token_file)) { $token = file_get_contents($token_file); }

$logs = array_diff(scandir($config->log_dir), array('..', '.'));
sort($logs);
$logs = array_reverse($logs);
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
  background-color: #fff;
  background-image: url('/assets/bg.jpeg');
  background-size: cover;
  max-height: 100vh;
  height: 100vh;
  padding: 20px;
  color: rgba(255, 255, 255, 0.7);
  font-family: 'Poppins';
}

a {

  color: rgba(255, 255, 255, 0.7);
}

::-webkit-scrollbar {
    width: 12px;
}
 
::-webkit-scrollbar-track {
    box-shadow: none;
    padding: 2px;
}
 
::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    margin: 2px;
}

.sidebar {
  width: 300px;
  margin-right: 20px;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.controls, .logs {
  padding: 20px;
  width: 100%;
  background-color: rgba(0, 0, 0, 0.3);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
  margin-bottom: 20px;
}

.logs {
  overflow-y: scroll;
  margin-bottom: 0;
}

.logs a {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
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
  text-transform: uppercase;
  border: none;
  padding: 0.5em 1em;
  width: 100%;
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
  margin-bottom: 10px;
}

button:disabled, button:hover {
  margin-top: 1px;
  border-width: 2px;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}

button.kill {
  display: none;
  background: #dc4a42;
  border-color: #ae3433;
}


button.kill.active {
  display: block;
}

#jobs {
  display: none;
}

#jobs.active {
  display: block;
}

button:active {
  margin-top: 3px;
  border-width: 0px;
  box-shadow: none;
}

.output-wrapper {
  width: 800px;
  height: 100%;
  display: flex;
  flex-direction: column;
}

#output {
  display: block;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
  background-color: rgba(0, 0, 0, 0.5);
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
let token = "<?= addslashes($token) ?>";
</script>
</head>
<body>
<div class="sidebar">
  <div class="controls">

<div id="jobs">
<?php foreach ($config->jobs as $job_id => $job) { ?>
  <button class="tertiary" value="<?= $job_id ?>"><?= $job->name ?></button>
<?php } ?>
</div>
  <button class="tertiary kill" id="kill">Kill job</button>
  <div class="build-info">
    <div class="">PID: <span id="build-pid"></span></div> 
    <div class="logfile">Log file: <span id="build-logfile"></span></div> 
    <div class="">Running: <span id="build-status"></span></div> 
  </div>
</div>
<div class="logs">
<?php

foreach ($logs as $logfile) {
  echo "<a href=\"$config->public_log_path/$logfile\" target=\"_blank\">$logfile</a>";
}

?>
</div>
</div>

<div class="output-wrapper">
  <pre id="output"></pre>
</div>
<script>

const jobsElement = document.getElementById('jobs')
const jobButtons = [...document.querySelectorAll('#jobs button')];
const killButton = document.getElementById('kill');
const pidElement = document.getElementById('build-pid');
const logfileElement = document.getElementById('build-logfile');
const statusElement = document.getElementById('build-status');
const outputElement = document.getElementById('output');

let running = false;

function getLogPath(logfile) { return `${config.public_log_path}/${logfile}` }

jobButtons.forEach(button => {
  button.addEventListener('click', async () => {
    running = true;
    token = await fetch('?run&job=' + button.value).then(res => res.text());

    const { pid, logfile } = JSON.parse(token).payload;

    pidElement.innerText = pid
    poll()
  })
})

killButton.addEventListener('click', async () => {
  if (token) {
    await fetch(`?kill&token=${token}`)
    poll()
  }
})

let lastOutput = ''
const HEADER_REGEX = /^(=+)( .+ )(=+)/

function renderOutput(output) {
  if (lastOutput === output) return
  lastOutput = output

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

  outputElement.scrollTo({ top: outputElement.scrollHeight, behavior: 'smooth' })
}

async function poll() {
  if (token) {
    const { logfile } = JSON.parse(token).payload;
    logfileElement.innerHTML = `<a href="${getLogPath(logfile)}" target="_blank">${logfile}</a>`

    const status = await fetch(`?status&token=${token}`).then(res => res.json()).catch(() => ({ running: false }))

    running = status.running;

    killButton.classList.toggle('active', running)
    jobsElement.classList.toggle('active', !running)

    statusElement.innerText = status.running;

    renderOutput(await fetch(getLogPath(logfile)).then(res => res.text()))

    if (running) {
      setTimeout(poll, 500);
    }
  }
}

poll()

</script>
</body>
</html>