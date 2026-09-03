<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VTU Purchase Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f6f9; }
        .card { background: #fff; padding: 25px; border-radius: 8px; max-width: 450px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; font-weight: bold; margin-top: 15px; cursor: pointer; }
        pre { background: #eef2f5; padding: 10px; border-radius: 4px; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="card">
    <h2>A3 Data Test Purchase</h2>
    <form id="purchaseForm">
        <label>User ID:</label>
        <input type="number" id="user_id" value="1" required>

        <label>Service Network:</label>
        <select id="service_id">
            <option value="mtn-data">MTN Data</option>
            <option value="airtel-data">Airtel Data</option>
            <option value="glo-data">Glo Data</option>
            <option value="etisalat-data">9mobile Data</option>
        </select>

        <label>Variation Code (Data Plan):</label>
        <input type="text" id="variation_code" value="mtn-100mb-24hrs" required>

        <label>Phone Number:</label>
        <input type="text" id="phone" value="08011111111" required>

        <label>Amount (NGN):</label>
        <input type="number" id="amount" value="100" required>

        <button type="submit">Execute Purchase</button>
    </form>

    <h3>API Response:</h3>
    <pre id="responseOutput">Waiting for request...</pre>
</div>

<script>
document.getElementById('purchaseForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const output = document.getElementById('responseOutput');
    output.innerText = "Processing transaction...";

    const payload = {
        user_id: parseInt(document.getElementById('user_id').value),
        service_id: document.getElementById('service_id').value,
        variation_code: document.getElementById('variation_code').value,
        phone: document.getElementById('phone').value,
        amount: parseFloat(document.getElementById('amount').value)
    };

    try {
        const response = await fetch('/buy_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        output.innerText = JSON.stringify(data, null, 2);
    } catch (err) {
        output.innerText = "Error sending request: " + err;
    }
});
</script>

</body>
</html>
