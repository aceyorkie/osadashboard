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
$result = $conn->query("SELECT * FROM dtp_organization WHERE org_status = 'approved' AND org_course = 'BS IN INFORMATION TECHNOLOGY'");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orgs[] = $row;
    }
}

$orgActivity = [];
$activityResult = $conn->query(" SELECT o.org_code, COUNT(u.user_id) AS member_count FROM dtp_organization o LEFT JOIN user_organizations u 
        ON o.org_code = u.organization_name 
        AND u.status = 'approved'
    WHERE o.org_status = 'approved' 
      AND o.org_course IN ('BS IN INFORMATION TECHNOLOGY', 'BS IN COMPUTER ENGINEERING')
    GROUP BY o.org_code
    ORDER BY member_count DESC
    LIMIT 5
");

if ($activityResult && $activityResult->num_rows > 0) {
    while ($row = $activityResult->fetch_assoc()) {
        $orgActivity[] = $row;
    }
}


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
    <link rel="stylesheet" href="css/2style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
</head>
<body>
    <nav>
        <div class="nav-left">
          <img src="greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>
        <div class="nav-center">
          <a href="#organizations" onclick="dept()">Departments</a>
          <a href="#approvals" onclick="approvals()">Approvals</a>
          <a href="#about" onclick="about()">About</a>
        </div>
    </nav>
    
    <div class="body-container">

    <div class="dashboard-container">

    <!-- Summary Analytics Cards -->
    <div class="analytics-cards">
        <div class="analytics-card">
            <h3>Total Organizations</h3>
            <p><?php echo count($orgs); ?></p>
        </div>
        <div class="analytics-card">
            <h3>Active Courses</h3>
            <p>2</p>
        </div>
        <div class="analytics-card">
            <h3>Avg Members</h3>
            <p>—</p>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="chart-container">
        <h2>Most Active Organizations</h2>
        <canvas id="activeOrgsChart"></canvas>
    </div>

    <!-- Organizations Grid -->
    <div class="org-grid">
        <?php foreach ($orgs as $org): ?>
            <div class="org-card">
                <div class="org-header">
                    <img class="org-logo" src="<?php echo $org['org_logo']; ?>">
                    <div>
                        <h3><?php echo htmlspecialchars($org['org_code']); ?></h3>
                        <span><?php echo htmlspecialchars($org['org_name']); ?></span>
                    </div>
                </div>
                <p class="org-description"><?php echo htmlspecialchars($org['org_description']); ?></p>
                <div class="org-actions">
                    <button onclick="openAboutOrganization('desc_<?php echo $org['org_code']; ?>')">About</button>
                    <button onclick="viewMembers('<?php echo $org['org_code']; ?>')">Members</button>
                    <button onclick="window.location.href='archive.php?org=<?php echo urlencode($org['org_code']); ?>'">Archives</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

</div>


    <footer>
        <div class="footer-left">
           <p>&copy; 2024 AJNova Platforms. All rights reserved.</p>
        </div>
        <div class="footer-content">
             <img src="greetings/footerlogo.png" alt="Logo" class="logo">  
        </div>
        <div class="footer-right">
            <a href="#home" onclick="home()">Home</a>
            <a href="#organizations" onclick="org()">Organizations</a>
            <a href="#about" onclick="about()">About</a>
            <a href="#profile" onclick="profile()">Profile</a>
        </div>
    </footer>


    <script>
        function dept() {
            window.location.href = 'Osa.html';
        }
        function approvals() {
            window.location.href = 'approvals.php';
        }
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
            fetch(`view_members.php?organization=${encodeURIComponent(organization)}`)
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
const ctx = document.getElementById('activeOrgsChart').getContext('2d');

const activeOrgsData = {
    labels: <?php echo json_encode(array_column($orgActivity, 'org_code')); ?>,
    datasets: [{
        label: 'Number of Members',
        data: <?php echo json_encode(array_column($orgActivity, 'member_count')); ?>,
        fill: false,
        borderColor: '#bc0000',
        backgroundColor: '#dace4e',
        tension: 0.3,
        pointBackgroundColor: '#bc0000',
        pointBorderColor: '#fff',
        pointHoverRadius: 6
    }]
};

new Chart(ctx, {
    type: 'line',
    data: activeOrgsData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                labels: {
                    color: '#333',
                    font: { weight: 'bold' }
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Organization' }
            },
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Members' }
            }
        }
    }
});
</script>

</body>
</html>
