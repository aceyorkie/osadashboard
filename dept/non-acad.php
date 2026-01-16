<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$orgs = [];

$sql = "
SELECT *
FROM nonacad_organization
WHERE org_status = 'approved'
ORDER BY org_code
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orgs[] = $row;
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/> 
    <link rel="stylesheet" href="/osaDashboard/css/dept.css">
    <link rel="stylesheet" href="/userHomeCopy/UserHome/css/nav.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>     
</head>
<body>
    <header class="top-nav">
      <div class="top-nav-left">
        <img src="/osaDashboard/greetings/umdc-logo.png" alt="Logo" class="top-logo">
      </div>
    </header>
    <nav>
      <div class="nav-center">
        <a href="/osaDashboard/Osa.php"><i class="fa-solid fa-house"></i><span>Departments</span></a>
        <a href="approvals.php"><i class="fa-solid fa-archive"></i><span>Approvals</span></a>
        <a href="php/calendar.php"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
      </div>
    </nav>
    
    <div class="main-content">
        <div class="body-container">

            <div class="dashboard-container">
            <div class="analytics-cards">
                <div class="analytics-card">
                    <h3>Total Organizations</h3>
                    <p><?php echo count($orgs); ?></p>
                </div>
            </div>

            <div class="org-grid">
                <?php foreach ($orgs as $org): ?>
                    <div class="org-card">
                        <div class="org-header">
                            <div>
                                <h3><?php echo htmlspecialchars($org['org_code']); ?></h3>
                                <span><?php echo htmlspecialchars($org['org_name']); ?></span>
                            </div>
                        </div>
                        <p class="org-description"><?php echo htmlspecialchars($org['org_description']); ?></p>
                        <div class="org-actions">
                            <button onclick="loadAnalytics('<?php echo $org['org_code']; ?>')">Analytics</button>
                            <button onclick="viewMembers('<?php echo $org['org_code']; ?>')">Members</button>
                            <button onclick="window.location.href='/osaDashboard/archive.php?org=<?php echo urlencode($org['org_code']); ?>'">Archives</button>
                            <button onclick="window.location.href='/osaDashboard/view_aop.php?org=<?php echo urlencode($org['org_code']); ?>'">AOP</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
            </div>
            <div id="analyticsPanel" class="analytics-panel">
                    <h2 id="analyticsTitle" class="analytics-title"></h2>

                    <div class="analytics-chart-wrapper">
                        <canvas id="analyticsChart"></canvas>
                    </div>

                    <div id="analyticsSummary" class="analytics-summary"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function aboutOrganization(descriptionBoxId) {
            document.getElementById(descriptionBoxId).style.display = 'block';
        }
        function closeBox(boxId) {
            document.getElementById(boxId).style.display = 'none';
        }
        function openAboutOrganization(descriptionBoxID) {
            document.getElementById('overlay').style.display = 'block';

        document.querySelectorAll('.aboutcontainer').forEach(container => {
            container.style.display = 'none';
        });

        document.getElementById(descriptionBoxID).style.display = 'block';
        }

        function closeAboutContainer() {
            document.getElementById('overlay').style.display = 'none';

            document.querySelectorAll('.aboutcontainer').forEach(container => {
                container.style.display = 'none';
            });
        }
        function viewMembers(organization) {
            fetch(`/osaDashboard/php/view_members.php?organization=${encodeURIComponent(organization)}`)
                .then(response => response.json())
                .then(data => {
                    const members = data.members;
                    const totalMembers = data.total_members;

                    if (members.length > 0) {
                        let membersList = `<h2 style="font-family: Roboto, serif; color: maroon; text-align: center"; >Members of ${organization} <br> (${totalMembers} Members)</h2><table style="width:100%;">
                            <thead>
                                <tr style="font-family: Roboto", serif;>
                                    <th>User ID</th>
                                    <th>Name</th>
                                    <th>Joined At</th>
                                </tr>
                            </thead>
                            <tbody>`;
                        members.forEach(member => {
                            membersList += `
                                <tr style="font-family: Roboto", serif; >
                                    <td>${member.user_id}</td>
                                    <td>${member.username}</td>
                                    <td>${member.joined_at}</td>
                                </tr>`;
                        });
                        membersList += `</tbody></table>
                            <button onclick="closeMembers()" class="close-button">Close</button>`;

                        document.body.innerHTML += `<div id="membersContainer">${membersList}</div>`;
                    } else {
                        alert(`No members have joined ${organization} yet.`);
                    }
                })
                .catch(error => console.error('Error fetching members:', error));
        }

        function closeMembers() {
            document.getElementById('membersContainer').remove();
        }
    </script>

<script>
let analyticsChart = null;

function loadAnalytics(orgCode) {

    fetch("/osaDashboard/php/fetch_org_analytics.php?org=" + orgCode)
        .then(res => res.json())
        .then(data => {

            document.getElementById("analyticsTitle").innerHTML =
                "Engagement Overview: " + data.org;

            // Show panel
            document.getElementById("analyticsPanel").style.display = "block";

            if (analyticsChart) analyticsChart.destroy();

            const ctx = document.getElementById("analyticsChart");

            analyticsChart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: [
                        "January","February","March","April","May","June",
                        "July","August","September","October","November","December"
                    ],
                    datasets: [
                        {
                            label: "Events Per Month",
                            data: data.events_per_month,
                            fill: true,
                            borderColor: "#990000",
                            backgroundColor: "rgba(153,0,0,0.25)",
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 6,
                            pointBackgroundColor: "#990000",
                            pointBorderColor: "#fff",
                        },
                        {
                            label: "Posts Per Month",
                            data: data.posts_per_month,
                            fill: true,
                            borderColor: "#f5c400",
                            backgroundColor: "rgba(255,193,7,0.25)",
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 6,
                            pointBackgroundColor: "#f5c400",
                            pointBorderColor: "#fff",
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { font: { size: 14 } }
                        },
                        x: {
                            ticks: { font: { size: 14 } }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { font: { size: 14 } }
                        }
                    }
                }
            });

            // SUMMARY BELOW GRAPH
            document.getElementById("analyticsSummary").innerHTML = `
                <strong>Events:</strong> highest in <b>${data.events_peak_month}</b> (${data.events_peak})<br>
                <strong>Posts:</strong> highest in <b>${data.posts_peak_month}</b> (${data.posts_peak})<br><br>
                • The highest combined engagement was in <b>${data.best_month}</b>.<br>
                • Low engagement detected in: <b>${data.low_months}</b>.
            `;
        });
}
</script>


</body>
</html>
