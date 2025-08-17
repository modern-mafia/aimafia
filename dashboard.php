<?php
include "includes/session.php";
include "includes/db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mafia Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

  <style>
    body {
      background: linear-gradient(135deg, #0f0f0f, #1c1c1c, #2a2a2a);
      background-size: 400% 400%;
      animation: gradientShift 20s ease infinite;
      font-family: 'Inter', sans-serif;
    }
    @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
  </style>
</head>
<body class="text-gray-200">

  <!-- Top Nav -->
  <nav class="fixed top-0 left-0 w-full h-14 bg-black/70 backdrop-blur flex justify-center space-x-8 items-center z-50 shadow-lg">
    <a href="inventory.php" class="hover:text-emerald-400 transition" title="Inventory"><i class="fas fa-box"></i></a>
    <a href="loadout.php" class="hover:text-emerald-400 transition" title="Loadout"><i class="fas fa-gun"></i></a>
    <a href="play.php" class="hover:text-emerald-400 transition" title="Play"><i class="fas fa-play"></i></a>
    <a href="store.php" class="hover:text-emerald-400 transition" title="Store"><i class="fas fa-store"></i></a>
  </nav>

  <div class="flex pt-14 h-screen">
    <!-- Left Panel -->
    <aside class="w-1/5 bg-black/50 backdrop-blur p-4 overflow-y-auto">
      <h2 class="text-lg font-bold mb-3">Missions</h2>
      <ul class="space-y-2">
        <li><a href="#" class="block p-2 rounded hover:bg-emerald-500/30">Street Hustle</a></li>
        <li><a href="#" class="block p-2 rounded hover:bg-emerald-500/30">Car Heist</a></li>
      </ul>
      <h2 class="text-lg font-bold mt-6 mb-3">Events</h2>
      <ul class="space-y-2">
        <li><a href="#" class="block p-2 rounded hover:bg-emerald-500/30">Weekly Tournament</a></li>
      </ul>
    </aside>

    <!-- Center Panel -->
    <main class="flex-1 flex flex-col items-center justify-center relative px-6">
      <!-- Player Card -->
      <div class="bg-black/60 p-6 rounded-2xl shadow-lg text-center space-y-4">
        <img src="assets/avatars/<?php echo $_SESSION['avatar']; ?>" 
             alt="Avatar" class="w-24 h-24 rounded-full mx-auto border-4 border-emerald-500 shadow-lg">
        <h1 class="text-2xl font-bold text-emerald-400"><?php echo $_SESSION['username']; ?></h1>
        <p class="text-gray-400">Rank: <span class="text-emerald-300"><?php echo $_SESSION['rank']; ?></span></p>
        <p class="text-gray-300">💵 $<?php echo number_format($_SESSION['cash']); ?></p>
        <div class="w-full bg-gray-700 rounded-full h-3">
          <div class="bg-emerald-500 h-3 rounded-full" style="width: <?php echo $_SESSION['xp'] ?? 50; ?>%;"></div>
        </div>
        <button class="mt-4 bg-emerald-500 hover:bg-emerald-600 px-6 py-2 rounded-xl font-bold shadow-lg transition">
          Big Action
        </button>
      </div>

      <!-- Weekly Spin (hidden unless active) -->
      <?php if ($showWeeklySpin ?? true): ?>
      <div id="weeklySpin" class="absolute top-20 w-3/4 bg-black/70 backdrop-blur rounded-xl p-4 shadow-lg hidden">
        <div class="flex items-center space-x-2 overflow-x-hidden relative">
          <div id="spinStrip" class="flex space-x-4">
            <div class="w-24 h-24 bg-gray-800 flex items-center justify-center rounded-lg">💵</div>
            <div class="w-24 h-24 bg-gray-800 flex items-center justify-center rounded-lg">💎</div>
            <div class="w-24 h-24 bg-gray-800 flex items-center justify-center rounded-lg">🔫</div>
            <div class="w-24 h-24 bg-gray-800 flex items-center justify-center rounded-lg">🏎️</div>
          </div>
          <div class="absolute inset-y-0 left-1/2 border-l-2 border-yellow-400 pointer-events-none"></div>
        </div>
      </div>
      <script>
        gsap.to("#weeklySpin", {y: 0, opacity: 1, display: "block", duration: 1, ease: "power2.out"});
      </script>
      <?php endif; ?>
    </main>

    <!-- Right Panel -->
    <aside class="w-1/5 bg-black/50 backdrop-blur p-4 overflow-y-auto">
      <h2 class="text-lg font-bold mb-3">Online</h2>
      <ul class="space-y-2">
        <li class="flex items-center space-x-2"><span class="w-2 h-2 bg-green-500 rounded-full"></span><span>User1</span></li>
        <li class="flex items-center space-x-2"><span class="w-2 h-2 bg-yellow-500 rounded-full"></span><span>User2</span></li>
      </ul>
      <h2 class="text-lg font-bold mt-6 mb-3">Activity</h2>
      <div class="space-y-2">
        <p class="text-sm">🔥 PlayerX won $5000 in Blackjack</p>
        <p class="text-sm">🚗 PlayerY stole a car</p>
      </div>
    </aside>
  </div>

</body>
</html>
