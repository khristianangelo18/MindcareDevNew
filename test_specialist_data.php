<?php
session_start();
include 'supabase.php';

echo "<h1>🔍 Recommendations Debug Tool</h1>";
echo "<style>
  body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
  .section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .success { color: #28a745; font-weight: bold; }
  .error { color: #dc3545; font-weight: bold; }
  .warning { color: #ffc107; font-weight: bold; }
  pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style>";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
  echo "<div class='section'>";
  echo "<p class='error'>❌ No user logged in. Please <a href='login.php'>login first</a>.</p>";
  echo "</div>";
  exit;
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'];

echo "<div class='section'>";
echo "<h2>👤 Current Session User</h2>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>Name:</strong> $user_name</p>";
echo "</div>";

// TEST 1: Query WITHOUT RLS bypass (current implementation)
echo "<div class='section'>";
echo "<h2>📋 Test 1: Query WITHOUT RLS Bypass</h2>";
echo "<p>This is what's currently happening in recommendations.php</p>";

$assessments_no_rls = supabaseSelect(
  'assessments',
  ['user_id' => $user_id],
  '*',
  'created_at.desc',
  1
  // Note: NO bypassRLS parameter = false by default
);

echo "<p><strong>Query:</strong> SELECT * FROM assessments WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1</p>";
echo "<p><strong>Result:</strong></p>";

if (empty($assessments_no_rls)) {
  echo "<p class='error'>❌ No assessments found (RLS may be blocking access)</p>";
  echo "<pre>[]</pre>";
} else {
  echo "<p class='success'>✅ Found " . count($assessments_no_rls) . " assessment(s)</p>";
  echo "<pre>" . json_encode($assessments_no_rls, JSON_PRETTY_PRINT) . "</pre>";
}
echo "</div>";

// TEST 2: Query WITH RLS bypass (like other working pages)
echo "<div class='section'>";
echo "<h2>📋 Test 2: Query WITH RLS Bypass</h2>";
echo "<p>This is what dashboard.php and other working pages use</p>";

$assessments_with_rls = supabaseSelect(
  'assessments',
  ['user_id' => $user_id],
  '*',
  'created_at.desc',
  1,
  true  // ← This bypasses RLS using SERVICE_KEY
);

echo "<p><strong>Query:</strong> SELECT * FROM assessments WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1 (with SERVICE_KEY)</p>";
echo "<p><strong>Result:</strong></p>";

if (empty($assessments_with_rls)) {
  echo "<p class='error'>❌ Still no assessments found (data may not exist)</p>";
  echo "<pre>[]</pre>";
} else {
  echo "<p class='success'>✅ Found " . count($assessments_with_rls) . " assessment(s)</p>";
  echo "<pre>" . json_encode($assessments_with_rls, JSON_PRETTY_PRINT) . "</pre>";
}
echo "</div>";

// TEST 3: Get ALL assessments for this user (no limit)
echo "<div class='section'>";
echo "<h2>📋 Test 3: Get ALL Assessments for User</h2>";

$all_assessments = supabaseSelect(
  'assessments',
  ['user_id' => $user_id],
  '*',
  'created_at.desc',
  null,  // No limit
  true
);

echo "<p><strong>Total Assessments Found:</strong> " . count($all_assessments) . "</p>";

if (empty($all_assessments)) {
  echo "<p class='error'>❌ No assessments exist for user_id = $user_id</p>";
} else {
  echo "<p class='success'>✅ Found " . count($all_assessments) . " assessment(s) total</p>";
  echo "<table style='width:100%; border-collapse: collapse; margin-top: 10px;'>";
  echo "<tr style='background: #f8f9fa;'>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>Score</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>Summary</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>Created At</th>";
  echo "</tr>";
  
  foreach ($all_assessments as $assessment) {
    echo "<tr>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['id'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['score'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($assessment['summary']) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['created_at'] . "</td>";
    echo "</tr>";
  }
  
  echo "</table>";
}
echo "</div>";

// TEST 4: Check if there are ANY assessments in the table
echo "<div class='section'>";
echo "<h2>📋 Test 4: Check Total Assessments in Database</h2>";

$total_assessments = supabaseSelect(
  'assessments',
  [],  // No filters
  'id,user_id,score,summary,created_at',
  'created_at.desc',
  10,  // Limit to 10
  true
);

echo "<p><strong>Recent Assessments in Database (any user):</strong></p>";

if (empty($total_assessments)) {
  echo "<p class='error'>❌ No assessments exist in the entire database</p>";
} else {
  echo "<p class='success'>✅ Found " . count($total_assessments) . " recent assessment(s)</p>";
  echo "<table style='width:100%; border-collapse: collapse; margin-top: 10px;'>";
  echo "<tr style='background: #f8f9fa;'>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>ID</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>User ID</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>Score</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>Summary</th>";
  echo "<th style='border: 1px solid #ddd; padding: 8px;'>Created At</th>";
  echo "</tr>";
  
  foreach ($total_assessments as $assessment) {
    $highlight = ($assessment['user_id'] == $user_id) ? "background: #d4edda;" : "";
    echo "<tr style='$highlight'>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['id'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['user_id'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['score'] . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($assessment['summary']) . "</td>";
    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $assessment['created_at'] . "</td>";
    echo "</tr>";
  }
  
  echo "</table>";
  echo "<p style='margin-top: 10px;'><em>Note: Your assessments are highlighted in green</em></p>";
}
echo "</div>";

// SOLUTION
echo "<div class='section' style='background: #d4edda; border-left: 4px solid #28a745;'>";
echo "<h2>✅ Solution</h2>";

if (!empty($assessments_with_rls) || !empty($all_assessments)) {
  echo "<p class='success'><strong>FOUND THE ISSUE!</strong></p>";
  echo "<p>Your assessments exist in the database, but the query needs to use <code>bypassRLS = true</code> parameter.</p>";
  echo "<p><strong>Fix:</strong> Update line 19 in recommendations.php to include the <code>true</code> parameter at the end:</p>";
  echo "<pre>
\$assessments = supabaseSelect(
  'assessments',
  ['user_id' => \$user_id],
  '*',
  'created_at.desc',
  1,
  true  // ← Add this parameter to bypass RLS
);
</pre>";
} else {
  echo "<p class='warning'><strong>No assessments found for your user ID</strong></p>";
  echo "<p>This could mean:</p>";
  echo "<ul>";
  echo "<li>You haven't completed an assessment yet</li>";
  echo "<li>The assessment data wasn't saved properly</li>";
  echo "<li>The user_id in the assessment doesn't match your session user_id</li>";
  echo "</ul>";
  echo "<p><strong>Next Steps:</strong></p>";
  echo "<ul>";
  echo "<li>Go to <a href='assessment.php'>assessment.php</a> and complete an assessment</li>";
  echo "<li>Check if the assessment is being saved with correct user_id</li>";
  echo "</ul>";
}
echo "</div>";

echo "<div class='section'>";
echo "<p><a href='recommendations.php' class='btn btn-primary' style='display: inline-block; padding: 10px 20px; background: #5ad0be; color: white; text-decoration: none; border-radius: 5px;'>← Back to Recommendations</a></p>";
echo "</div>";
?>