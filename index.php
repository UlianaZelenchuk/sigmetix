<?php

require_once('config/config.php');

$mysqli = new mysqli($host, $userName, $password, $dbName);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$users = getUsersList($mysqli);
$userKeys = array(
    'uid',
    'firstName',
    'lastName',
    'birthDay',
    'dateChange',
    'description'
);
$errors = [];

if (isset($_POST) && isset($_POST['import'])) {
    if (!empty($_FILES['file']['name']) && !empty($_FILES['file']['tmp_name'])) {
        $csvPath = $_FILES['file']['tmp_name'];
        $dataResult = array(
            'inserted' => 0,
            'deleted' => 0,
            'updated' => 0
        );

        $dataUsers = [];
        $usersToAdd = [];
        $file = fopen($csvPath,"r");
        while (($data = fgetcsv($file, 10000, ",")) !== false) {
            if (count($data) !== count($userKeys)) {
                $errors[] = 'Wrong count of columns in row';
                continue;
            }
            $dataUser = array_combine($userKeys, $data);
            $dataUsers[$data[0]] = $dataUser;

            foreach ($users as $user) {
                if ($user['uid'] === $dataUser['uid'] && $user['dateChange'] !== $dataUser['dateChange']) {
                    $dataResult['updated'] += updateUserInfo($mysqli, $dataUser);
                }
            }

            if (!array_key_exists($dataUser['uid'], $users)) {
                $usersToAdd[] = sprintf('("%s")', implode('", "', $dataUser));
            }
        }

        if (!empty($usersToAdd)) {
            $dataResult['inserted'] = addUser($mysqli, $usersToAdd);
        }

        $usersToDelete = array_diff_key($users, $dataUsers);
        if (!empty($usersToDelete)) {
            $dataResult['deleted'] = deleteUsers($mysqli, array_keys($usersToDelete));
        }

        $users = getUsersList($mysqli);
    } else {
        $errors[] = 'Error while loading file';
    }
}
$errors = implode(", <br>", $errors);

include "templates/index.html";

/**
 * @param mysqli $mysqli
 *
 * @return array
 */
function getUsersList($mysqli)
{
    $users  = [];
    $query  = sprintf('SELECT * FROM users');
    $result = $mysqli->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[$row['uid']] = $row;
        }
    }

    return $users;
}
/**
 * @param mysqli $mysqli
 * @param array  $users
 *
 * @return int
 */
function addUser($mysqli, $users)
{
    $query = sprintf('INSERT INTO users(uid, firstName, lastName, birthDay, dateChange, description)
                VALUES %s', implode(", ", $users));
    $mysqli->query($query);

    return $mysqli->affected_rows;
}

/**
 * @param mysqli $mysqli
 * @param array  $userInfo
 *
 * @return int
 */
function updateUserInfo($mysqli, $userInfo)
{
    $query = sprintf(
        'UPDATE users SET firstName="%s", lastName="%s", birthDay="%s", dateChange="%s", description = "%s" 
                WHERE uid="%d"',
        $userInfo['firstName'],
        $userInfo['lastName'],
        $userInfo['birthDay'],
        $userInfo['dateChange'],
        $userInfo['description'],
        $userInfo['uid']);
    $mysqli->query($query);

    return $mysqli->affected_rows;
}

/**
 * @param mysqli $mysqli
 * @param array  $uids
 *
 * @return int
 */
function deleteUsers($mysqli, $uids)
{
    $query = sprintf('DELETE FROM users WHERE uid in (%s)', implode(', ', $uids));
    $mysqli->query($query);

    return $mysqli->affected_rows;
}
