<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
</head>
<body style="margin: 0; padding: 0; background-color: white;">
    <!-- Header Section -->
    <div style="max-width: 600px; margin: 0 auto; background-color: #2C2C2C; padding: 10px; text-align: center; color: white;">
        <div style="padding: 10px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 10px;">
            <svg width="104" height="26" viewBox="0 0 104 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M27.0654 25.4846H20.5875L18.4282 19.7961H8.80265L6.71178 25.5H0.5L10.7947 0.5H16.6643L27.0654 25.4846ZM16.4818 14.6247L13.5242 7.00664L10.7339 14.6247H16.4818ZM38.8807 25.4846H47.7004V20.4213H39.1088C37.3601 20.447 36.0625 20.0611 35.216 19.2635C34.3695 18.4659 33.9463 17.1795 33.9463 15.4043H47.7004V10.3333H33.9311C34.2099 7.15843 35.9231 5.57101 39.0708 5.57101H47.7004V0.5H39.0176C34.9626 0.5 31.9948 1.65262 30.1143 3.95786C28.4011 6.06242 27.5444 9.21668 27.5444 13.4207C27.5444 17.8716 28.7914 21.1494 31.2852 23.2539C32.3211 24.1392 33.5438 24.7704 34.8587 25.0986C36.18 25.386 37.5298 25.5156 38.8807 25.4846ZM79.4968 25.4846H73.8021L74.1594 9.10605L68.8828 25.4846H63.0436L57.6073 8.86678L57.9875 25.4846H52.1711V0.515437H60.1392L65.8492 18.0749L71.4983 0.515437H79.4968V25.4846ZM94.6727 25.4846H103.492L103.5 20.3827H94.916C92.7821 20.3827 91.3197 19.7755 90.529 18.5611C89.9005 17.5783 89.5862 15.9291 89.5862 13.6136C89.5862 9.32731 90.5417 6.7545 92.4526 5.89518C93.2359 5.59364 94.0715 5.45708 94.9084 5.49383H103.492V0.5H94.8096C90.7546 0.5 87.7868 1.65262 85.9063 3.95786C84.193 6.06242 83.3364 9.21668 83.3364 13.4207C83.3364 17.8716 84.5833 21.1494 87.0772 23.2539C88.1131 24.1392 89.3358 24.7704 90.6507 25.0986C91.9719 25.3864 93.3218 25.5159 94.6727 25.4846Z" fill="white"/>
            </svg>  
        </div>
    </div>

    <!-- Content Section -->
    <div style="background-color: white; max-width: 600px; margin: 0 auto; padding: 20px; text-align: center; color: black;">
        @yield('content')
    </div>

    <!-- Footer Section -->
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; text-align: center; background-color: white; color: #333;">
        <p style="text-align: center; color: #333;">&copy; 2024 {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

