<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A3 Data Test Console</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f6f9; }
        .card { max-width: 500px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; }
        button { background: #007bff; color: #fff; border: none; border-radius: 4px; font-weight: bold; margin-top: 15px; cursor: pointer; }
        pre { background: #eef2f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>

<div class="card">
    <h2>A3 Data Test Purchase</h2>
    
    <label>User ID:</label>
    <input type="number" id="userId" value="1">

    <label>Service Type:</label>
    <select id="serviceNetwork" onchange="loadPlans()">
        <optgroup label="Data Services">
            <option value="mtn-data">MTN Data</option>
            <option value="airtel-data">Airtel Data</option>
            <option value="glo-data">Glo Data</option>
            <option value="etisalat-data">9mobile Data</option>
        </optgroup>
        <optgroup label="Airtime Top-up">
            <option value="mtn">MTN Airtime</option>
            <option value="airtel">Airtel Airtime</option>
            <option value="glo">Glo Airtime</option>
            <option value="etisalat">9mobile Airtime</option>
        </optgroup>
    </select>

    <div id="variationContainer">
        <label>Select Plan / Variation:</label>
        <select id="variationCode" onchange="updateAmount()"></select>
    </div>

    <label>Phone Number:</label>
    <input type="text" id="phone" value="08011111111">

    <label>Amount (NGN):</label>
    <input type="number" id="amount" value="50">

    <button onclick="buy()">Execute Purchase</button>

    <h3>API Response:</h3>
    <pre id="response">Select network to load options...</pre>
</div>

<script>
let currentPlans = [];

async function loadPlans() {
    const network = document.getElementById('serviceNetwork').value;
    const varContainer = document.getElementById('variationContainer');
    const varSelect = document.getElementById('variationCode');
    const amountInput = document.getElementById('amount');

    if (!network.includes('data')) {
        varContainer.style.display = 'none';
        amountInput.readOnly = false;
        amountInput.value = 100;
        return;
    }

    varContainer.style.display = 'block';
    varSelect.innerHTML = '<option>Loading plans from VTpass...</option>';

    try {
        const res = await fetch(`/get_plans.php?network=${network}`);
        const data = await res.json();
        
        if (data.content && data.content.varations) {
            currentPlans = data.content.varations;
            varSelect.innerHTML = '';
            currentPlans.forEach(plan => {
                varSelect.innerHTML += `<option value="${plan.variation_code}" data-amount="${plan.variation_amount}">${plan.name} - ₦${plan.variation_amount}</option>`;
            });
            updateAmount();
        } else {
            varSelect.innerHTML = '<option value="">No plans found</option>';
        }
    } catch (err) {
        varSelect.innerHTML = '<option value="">Failed to fetch plans</option>';
    }
}

function updateAmount() {
    const select = document.getElementById('variationCode');
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.dataset.amount) {
        document.getElementById('amount').value = selectedOption.dataset.amount;
    }
}

async function buy() {
    document.getElementById('response').innerText = 'Processing request...';
    
    const payload = {
        user_id: parseInt(document.getElementById('userId').value),
        network: document.getElementById('serviceNetwork').value,
        variation_code: document.getElementById('variationCode').value,
        phone: document.getElementById('phone').value,
        amount: parseFloat(document.getElementById('amount').value)
    };

    try {
        const res = await fetch('/buy_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        document.getElementById('response').innerText = JSON.stringify(data, null, 2);
    } catch (err) {
        document.getElementById('response').innerText = 'Error: ' + err.message;
    }
}

loadPlans();
</script>

</body>
</html>
