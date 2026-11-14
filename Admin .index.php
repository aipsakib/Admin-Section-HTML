<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Panel - Device & User Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
  body {
    font-family: Arial, sans-serif;
    background:#f4f6f8;
    margin:0; padding:10px;
  }
  .container {
    max-width: 1000px;
    margin: auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,.15);
    overflow: hidden;
  }
  .header {
    background: #ffffff;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
  }
  .header h2 {
    margin: 0;
    font-size: 18px;
  }
  .btn {
    padding: 7px 12px;
    border: none;
    border-radius: 5px;
    color: #fff;
    cursor: pointer;
  }
  .logout {
    background: #e74c3c;
  }
  .add {
    background: #27ae60;
  }
  .alert {
    background: #e7f7ee;
    color: #155724;
    padding: 10px;
    font-size: 14px;
    display: none;
  }
  .table-wrap {
    overflow-x: auto;
    padding: 10px;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }
  th {
    background: #2c2f33;
    color: #fff;
    padding: 10px;
  }
  td {
    padding: 10px;
    border-bottom: 1px solid #eee;
  }
  .action-btn {
    border: none;
    padding: 6px 8px;
    cursor: pointer;
    border-radius: 4px;
    color: #fff;
    margin-right: 5px;
  }
  .edit {
    background: #e67e22;
  }
  .delete {
    background: #e74c3c;
  }
  .approve {
    background: #3498db;
  }
  /* Modal */
  .modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 999;
  }
  .modal-box {
    background: #fff;
    width: 350px;
    margin: 100px auto;
    padding: 20px;
    border-radius: 8px;
  }
  .modal-box h3 {
    margin-top: 0;
  }
  .modal-box input {
    width: 100%;
    padding: 8px;
    margin-top: 8px;
  }
</style>
</head>
<body>

<div class="container">

  <div class="header">
    <h2>Admin Panel - User & Device Management</h2>
    <div>
      <button class="btn logout" onclick="logout()">Logout</button>
      <button class="btn add" onclick="openUserModal()">Add User</button>
    </div>
  </div>

  <div class="alert" id="alert">✔ আপডেট হয়েছে!</div>

  <h3 style="margin-left:15px;">Pending Device Requests</h3>
  <div class="table-wrap" style="max-height:200px; overflow-y:auto;">
    <table>
      <thead>
        <tr>
          <th>Device ID</th>
          <th>Requested At</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="deviceRequests"></tbody>
    </table>
  </div>

  <h3 style="margin-left:15px;">Users List</h3>
  <div class="table-wrap" style="max-height:300px; overflow-y:auto;">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Device ID</th>
          <th>Password</th>
          <th>Expire</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="userTable"></tbody>
    </table>
  </div>

</div>

<!-- Modal for Add/Edit User -->
<div class="modal" id="modal">
  <div class="modal-box">
    <h3 id="modalTitle">Add User</h3>
    <input type="text" id="deviceIdInput" placeholder="Device ID (অবশ্যই দিতে হবে)" />
    <input type="password" id="passwordInput" placeholder="Password" />
    <input type="datetime-local" id="expireInput" />
    <br/><br/>
    <button class="btn add" onclick="saveUser()">Save</button>
    <button class="btn" style="background:#555; margin-left:10px;" onclick="closeModal()">Cancel</button>
  </div>
</div>

<!-- Firebase -->
<script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script>

<script>
// Firebase Config
var firebaseConfig = {
    apiKey: "AIzaSyD75FoFDTNcqD2TA-7jGcRVBGAKecnldkY",
    authDomain: "stexsmspy.firebaseapp.com",
    databaseURL: "https://stexsmspy-default-rtdb.firebaseio.com",
    projectId: "stexsmspy",
    storageBucket: "stexsmspy.firebasestorage.app",
    messagingSenderId: "550546103422",
    appId: "1:550546103422:android:ebb82217b4b1d55a96cc2f"
};
firebase.initializeApp(firebaseConfig);
var db = firebase.database();

let editId = null;

const alertBox = document.getElementById('alert');
const deviceRequestsTbody = document.getElementById('deviceRequests');
const userTableTbody = document.getElementById('userTable');

const modal = document.getElementById('modal');
const modalTitle = document.getElementById('modalTitle');
const deviceIdInput = document.getElementById('deviceIdInput');
const passwordInput = document.getElementById('passwordInput');
const expireInput = document.getElementById('expireInput');

function showAlert(msg = "✔ আপডেট হয়েছে!") {
  alertBox.innerText = msg;
  alertBox.style.display = "block";
  setTimeout(() => {
    alertBox.style.display = "none";
  }, 2000);
}

function openUserModal(){
  editId = null;
  modalTitle.innerText = "Add User";
  deviceIdInput.value = "";
  passwordInput.value = "";
  expireInput.value = "";
  modal.style.display = "block";
}

function closeModal(){
  modal.style.display = "none";
}

// Load Pending Device Requests
db.ref("devices").on("value", snap => {
  deviceRequestsTbody.innerHTML = "";
  snap.forEach(child => {
    const d = child.val();
    if(d.status === "pending"){
      deviceRequestsTbody.innerHTML += `
      <tr>
        <td>${d.deviceId}</td>
        <td>${d.requestTime}</td>
        <td style="color:orange;">${d.status}</td>
        <td>
          <button class="action-btn approve" onclick="approveDevice('${child.key}','${d.deviceId}')">Approve</button>
          <button class="action-btn delete" onclick="removeDevice('${child.key}')">Remove</button>
        </td>
      </tr>`;
    }
  });
});

// Approve Device Request (Open Modal prefill)
function approveDevice(key, deviceId){
  editId = null;
  modalTitle.innerText = "Add User for Device";
  deviceIdInput.value = deviceId;
  passwordInput.value = "";
  expireInput.value = "";
  modal.style.display = "block";

  // Remove the device request on approval (or set status=approved)
  db.ref("devices/"+key).update({status:"approved"});
}

// Remove Device Request
function removeDevice(key){
  if(confirm("Are you sure to remove this device request?")){
    db.ref("devices/"+key).remove();
  }
}

// Load Users List
db.ref("users").on("value", snap => {
  userTableTbody.innerHTML = "";
  snap.forEach(child => {
    const u = child.val();
    userTableTbody.innerHTML += `
    <tr>
      <td>${child.key}</td>
      <td>${u.deviceId}</td>
      <td>${u.pass}</td>
      <td>${u.expire}</td>
      <td>
        <button class="action-btn edit" onclick="editUser('${child.key}','${u.deviceId}','${u.pass}','${u.expire}')">Edit</button>
        <button class="action-btn delete" onclick="deleteUser('${child.key}')">Delete</button>
      </td>
    </tr>`;
  });
});

// Edit User
function editUser(id, deviceId, pass, expire){
  editId = id;
  modalTitle.innerText = "Edit User";
  deviceIdInput.value = deviceId;
  passwordInput.value = pass;
  expireInput.value = expire.replace(" ","T");
  modal.style.display = "block";
}

// Delete User
function deleteUser(id){
  if(confirm("Are you sure to delete this user?")){
    db.ref("users/"+id).remove();
    showAlert("User deleted!");
  }
}

// Save User (Add or Edit)
function saveUser(){
  const deviceIdVal = deviceIdInput.value.trim();
  const passVal = passwordInput.value.trim();
  const expireVal = expireInput.value;

  if(!deviceIdVal || !passVal || !expireVal){
    alert("Device ID, Password, ও Expire সময় অবশ্যই দিতে হবে!");
    return;
  }

  const data = {
    deviceId: deviceIdVal,
    pass: passVal,
    expire: expireVal.replace("T", " ")
  };

  if(editId){
    db.ref("users/"+editId).update(data);
    showAlert("User updated!");
  } else {
    db.ref("users").push(data);
    showAlert("New user added!");
  }

  modal.style.display = "none";
}

// Logout placeholder
function logout(){
  alert("Logout clicked. এখানে তোমার লগআউট কোড আসবে।");
}

</script>
</body>
</html>
