<?php

declare(strict_types=1);

namespace App\Data;

class ProfileFavorites
{
    /**
     * Allowed values for favorite city
     *
     * @return array<string>
     */
    public static function cities(): array
    {
        return [
            'Los Santos',
            'San Fierro',
            'Las Venturas',
        ];
    }

    /**
     * Allowed values for favorite vehicle
     *
     * @return array<string>
     */
    public static function vehicles(): array
    {
        return [
            "Landstalker", "Bravura", "Buffalo", "Linerunner", "Perennial", "Sentinel", "Dumper", "Fire Truck", "Trashmaster", "Stretch", "Manana",
  "Infernus", "Voodoo", "Pony", "Mule", "Cheetah", "Ambulance", "Leviathan", "Moonbeam", "Esperanto", "Taxi", "Washington", "Bobcat",
  "Mr. Whoopee", "BF Injection", "Hunter", "Premier", "Enforcer", "Securicar", "Banshee", "Predator", "Bus", "Rhino", "Barracks", "Hotknife",
  "Trailer 1", "Previon", "Coach", "Cabbie", "Stallion", "Rumpo", "RC Bandit", "Romero", "Packer", "Monster", "Admiral", "Squalo",
  "Seasparrow", "Pizzaboy", "Tram", "Trailer 2", "Turismo", "Speeder", "Reefer", "Tropic", "Flatbed", "Yankee", "Caddy", "Solair",
  "Berkley's RC Van", "Skimmer", "PCJ-600", "Faggio", "Freeway", "RC Baron", "RC Raider", "Glendale", "Oceanic", "Sanchez", "Sparrow", "Patriot",
  "Quadbike", "Coastguard", "Dinghy", "Hermes", "Sabre", "Rustler", "ZR-350", "Walton", "Regina", "Comet", "BMX", "Burrito", "Camper", "Marquis",
  "Baggage", "Dozer", "Maverick", "News Chopper", "Rancher", "FBI Rancher", "Virgo", "Greenwood", "Jetmax", "Hotring Racer", "Sandking",
  "Blista Compact", "Police Maverick", "Boxville", "Benson", "Mesa", "RC Goblin", "Hotring Racer 2", "Hotring Racer 3", "Bloodring Banger",
  "Rancher Lure", "Super GT", "Elegant", "Journey", "Bike", "Mountain Bike", "Beagle", "Cropduster", "Stuntplane", "Tanker", "Roadtrain", "Nebula",
  "Majestic", "Buccaneer", "Shamal", "Hydra", "FCR-900", "NRG-500", "HPV1000", "Cement Truck", "Towtruck", "Fortune", "Cadrona", "FBI Truck",
  "Willard", "Forklift", "Tractor", "Combine Harvester", "Feltzer", "Remington", "Slamvan", "Blade", "Freight", "Brown Streak", "Vortex", "Vincent",
  "Bullet", "Clover", "Sadler", "Fire Truck Ladder", "Hustler", "Intruder", "Primo", "Cargobob", "Tampa", "Sunrise", "Merit", "Utility Van",
  "Nevada", "Yosemite", "Windsor", "Monster 2", "Monster 3", "Uranus", "Jester", "Sultan", "Stratum", "Elegy", "Raindance", "RC Tiger", "Flash",
  "Tahoma", "Savanna", "Bandito", "Freight Train Flatbed", "Streak Train Trailer", "Kart", "Mower", "Dune", "Sweeper", "Broadway", "Tornado",
  "AT-400", "DFT-30", "Huntley", "Stafford", "BF-400", "Newsvan", "Tug", "Trailer (Tanker Commando)", "Emperor", "Wayfarer", "Euros", "Hotdog",
  "Club", "Box Freight", "Trailer 3", "Andromada", "Dodo", "RC Cam", "Launch", "Police LS", "Police SF", "Police LV", "Police Ranger",
  "Picador", "S.W.A.T.", "Alpha", "Phoenix", "Glendale Damaged", "Sadler Damaged", "Baggage Trailer (covered)",
  "Baggage Trailer (Uncovered)", "Trailer (Stairs)", "Boxville Mission", "Farm Trailer", "Street Clean Trailer"
        ];
    }

    /**
     * Allowed values for favorite character
     *
     * @return array<string>
     */
    public static function characters(): array
    {
        return [
            'Carl "CJ" Johnson',
        'Sweet Johnson', 'Kendl Johnson', 'Big Smoke', 'Ryder', 'Cesar Vialpando', 'Catalina', 'Claude', 'The Truth', 'Wu Zi Mu', 'Zero', 'Jizzy B.', 'T-Bone Mendez', 'Mike Toreno', 'Madd Dogg', 'OG Loc', 'Kent Paul', 'Maccer', 'Ken Rosenberg', 'Officer Frank Tenpenny', 'Officer Eddie Pulaski', 'Officer Jimmy Hernandez', 'Emmet'
        ];
    }

    /**
     * Allowed values for favorite gang
     *
     * @return array<string>
     */
    public static function gangs(): array
    {
        return [
            'Grove Street Families',
            'Ballas',
            'Los Santos Vagos',
            'Varios Los Aztecas',
            'San Fierro Rifa',
            'Da Nang Boys',
            'Triads',
            'Italian Mafia',
            'Russian Mafia',
            'Varrio Los Aztecas',
            'The Loco Syndicate'
        ];
    }

    /**
     * Allowed values for favorite weapon
     *
     * @return array<string>
     */
    public static function weapons(): array
    {
        return [
            'Fist',
            'Brassknuckle',
            'Golfclub',
            'Nightstick',
            'Knife',
            'Bat',
            'Shovel',
            'Poolstick',
            'Katana',
            'Chainsaw',
            'Colt 45',
            'Silenced',
            'Deagle',
            'Shotgun',
            'Sawed-off',
            'Combat Shotgun',~
            'Uzi',
            'MP5',
            'Tec-9',
            'AK-47',
            'M4',
            'Rifle',
            'Sniper',
            'Rocket Launcher',
            'Rocket Launcher HS',
            'Flamethrower',
            'Minigun',
            'Grenade',
            'Teargas',
            'Molotov',
            'Satchel',
            'Spraycan',
            'Fire Extinguisher',
            'Camera',
            'Dildo',
            'Purple Dildo',
            'Vibrator',
            'Silver Vibrator',
            'Flower',
            'Cane',
            'Nightvision Goggles',
            'Infrared Goggles',
            'Parachute',
            'Satchel Detonator'
        ];
    }

    /**
     * Allowed values for favorite radio station
     *
     * @return array<string>
     */
    public static function radioStations(): array
    {
        return [
            'Radio Los Santos',
            'Playback FM',
            'K-DST',
            'Bounce FM',
            'KJAH West',
            'Master Sounds 98.3',
            'CSR 103.9',
            'Radio X',
            'SF-UR',
            'West Coast Talk Radio',
            'K-Rose',
            'K-Jah Radio West',
            'User Track Player'
        ];
    }
}
