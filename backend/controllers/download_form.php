<?php
require_once __DIR__ . '/../repositories/database/db.php';
require_once __DIR__ . '/../services/JwtService.php';

$jwtService = new JwtService();
$token = $jwtService->getToken();
$data = $jwtService->verifyToken($token);

$application_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$format = $_GET['format'] ?? '';
$allowed_formats = ['csv', 'json'];

if (!$application_id || !in_array($format, $allowed_formats)) {
    http_response_code(400); // Bad Request
    die("Invalid request. Please provide a valid application ID and format (csv or json).");
}

$stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$application_data = $result->fetch_assoc();

if (!$application_data) {
    http_response_code(404); // Not Found
    die("Application not found or you do not have permission to view it.");
}

function get_friendly_labels()
{
    return [
        'address_line1' => 'Address Line 1',
        'address_line2' => 'Address Line 2',
        'postcode' => 'Postcode',
        'city' => 'City',
        'phone_number' => 'Phone Number',
        'has_garden' => 'Has Garden',
        'living_situation' => 'Living Situation',
        'household_setting' => 'Household Setting',
        'activity_level' => 'Activity Level',
        'num_adults' => 'Number of Adults',
        'num_children' => 'Number of Children',
        'youngest_child_age' => 'Youngest Child Age',
        'visiting_children' => 'Visiting Children',
        'visiting_children_ages' => 'Visiting Children Ages',
        'has_flatmates' => 'Has Flatmates',
        'has_allergies' => 'Allergies',
        'has_other_animals' => 'Has Other Animals',
        'other_animals_info' => 'Other Animals Info',
        'neutered' => 'Other Animals Neutered',
        'vaccinated' => 'Other Animals Vaccinated',
        'experience' => 'Previous Experience',
        'submitted_at' => 'Submission Date'
    ];
}

$labels = get_friendly_labels();
$filename = "adoption_application_" . $application_id;

if ($format === 'json') {
    $friendly_data = [];
    foreach ($labels as $key => $label) {
        if (isset($application_data[$key])) {
            $friendly_data[$label] = $application_data[$key];
        }
    }

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    echo json_encode($friendly_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} elseif ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, array_values($labels));

    $row_data = [];
    foreach (array_keys($labels) as $key) {
        $row_data[] = $application_data[$key] ?? '';
    }
    fputcsv($output, $row_data);

    fclose($output);
}

$stmt->close();
$conn->close();
exit();
