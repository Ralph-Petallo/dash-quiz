
function sumUp(number) {
    if (number <= 0) {
        return 0;
    }

    let total = 0;

    for (let i = 1; i <= number; i++) {
        total += i;
    }

    return total;
}

function ageCalculator(day, month, year) {
    const now = new Date();
    let age = now.getFullYear() - year
    let years = now.getFullYear();
    let days = now.getDay();
    let months = now.getMonth();
    console.log(years)
    console.log(days)
    console.log(months)

    if (month > now.getUTCMonth() && day > now.getUTCDay()) {
        age -= 1
    }

    return age;
}

console.log("Age: " + ageCalculator(2, 9, 2004))
