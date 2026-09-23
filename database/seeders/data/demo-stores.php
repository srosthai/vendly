<?php

/*
 * Local demo data: ten shops in different trades. Every store, category,
 * brand, and product name is unique across the set. Nothing here is a
 * real business.
 */

return [
    [
        'owner' => 'Sokha Chan', 'store' => 'Lotus Leaf Tea', 'accent' => 'green',
        'description' => 'Loose-leaf teas blended in Phnom Penh.',
        'address' => 'Street 240, Phnom Penh', 'hours' => 'Mon to Sat, 8:00 to 18:00',
        'price' => [250, 900],
        'brands' => ['Kep Hills', 'Mondulkiri Gardens', 'Lotus Leaf Blends'],
        'categories' => [
            'Green tea' => ['Jasmine green tea', 'Sencha', 'Genmaicha', 'Lotus green tea', 'Mint green tea', 'Lemongrass green tea', 'Pandan green tea', 'Ginger green tea', 'Gunpowder green tea'],
            'Black tea' => ['Breakfast black tea', 'Earl Grey', 'Masala chai', 'Smoked black tea', 'Honey black tea', 'Vanilla black tea', 'Cinnamon black tea', 'Peach black tea'],
            'Herbal tea' => ['Butterfly pea flower', 'Chamomile', 'Rosella', 'Lemon verbena', 'Moringa leaf', 'Tulsi', 'Peppermint', 'Rooibos'],
        ],
    ],
    [
        'owner' => 'Dara Kim', 'store' => 'Mekong Crafts', 'accent' => 'orange',
        'description' => 'Handmade baskets, carvings, and pottery from village workshops.',
        'address' => 'Riverside, Siem Reap', 'hours' => 'Daily, 9:00 to 20:00',
        'price' => [400, 4500],
        'brands' => ['Tonle Weavers', 'Village Kiln', 'Palm Hands'],
        'categories' => [
            'Baskets' => ['Rattan market basket', 'Water hyacinth tote', 'Bamboo fruit bowl', 'Palm leaf box', 'Lidded rice basket', 'Rattan laundry hamper', 'Round storage basket', 'Picnic basket', 'Mini gift basket'],
            'Wood carvings' => ['Apsara wall panel', 'Carved elephant', 'Teak serving board', 'Lotus candle holder', 'Carved key box', 'Wooden spoon set', 'Buddha head carving', 'Carved photo frame'],
            'Pottery' => ['Glazed tea cup', 'Clay water jar', 'Terracotta planter', 'Celadon bowl', 'Clay incense burner', 'Painted vase', 'Stoneware plate', 'Clay oil lamp'],
        ],
    ],
    [
        'owner' => 'Vanna Sok', 'store' => 'Phnom Bakes', 'accent' => 'pink',
        'description' => 'Fresh bread and cakes baked every morning.',
        'address' => 'Street 51, Phnom Penh', 'hours' => 'Tue to Sun, 7:00 to 19:00',
        'price' => [120, 2500],
        'brands' => ['Morning Oven', 'Sugar Palm Kitchen', 'Phnom Bakes House'],
        'categories' => [
            'Breads' => ['Country sourdough', 'Baguette', 'Brioche loaf', 'Whole wheat loaf', 'Coconut bun', 'Pandan milk bread', 'Garlic twist', 'Seeded rye', 'Cheese roll'],
            'Cakes' => ['Palm sugar sponge', 'Banana cake', 'Lemon drizzle cake', 'Chocolate fudge cake', 'Coconut layer cake', 'Carrot cake', 'Mango cheesecake', 'Red velvet slice'],
            'Cookies' => ['Butter cookies', 'Cashew crunch cookies', 'Oat raisin cookies', 'Sesame thins', 'Double chocolate cookies', 'Coconut macaroons', 'Ginger snaps', 'Peanut butter cookies'],
        ],
    ],
    [
        'owner' => 'Channary Lim', 'store' => 'Kampot Pepper Co', 'accent' => 'red',
        'description' => 'Pepper, salt, and sauces from the Kampot coast.',
        'address' => 'Old Market, Kampot', 'hours' => 'Mon to Sat, 8:00 to 17:00',
        'price' => [300, 1800],
        'brands' => ['Coastal Farm', 'Kampot Reserve', 'Salt Field Kep'],
        'categories' => [
            'Pepper' => ['Black pepper corns', 'Red pepper corns', 'White pepper corns', 'Fresh green pepper', 'Pepper grinder set', 'Long pepper', 'Crushed black pepper', 'Pepper tasting box', 'Pepper gift tin'],
            'Salt' => ['Flower of salt', 'Pepper sea salt', 'Lime sea salt', 'Chili sea salt', 'Smoked sea salt', 'Coarse sea salt', 'Herb sea salt', 'Garlic sea salt'],
            'Sauces' => ['Green pepper sauce', 'Kampot chili paste', 'Fish sauce reserve', 'Tamarind dipping sauce', 'Lemongrass marinade', 'Prahok spread', 'Peanut satay sauce', 'Sweet chili glaze'],
        ],
    ],
    [
        'owner' => 'Rithy Heng', 'store' => 'Riverside Shoes', 'accent' => 'blue',
        'description' => 'Everyday sneakers, sandals, and boots.',
        'address' => 'Sisowath Quay, Phnom Penh', 'hours' => 'Daily, 10:00 to 21:00',
        'price' => [1200, 6500],
        'brands' => ['Quay Walk', 'Stride Lab', 'Monsoon Trail'],
        'categories' => [
            'Sneakers' => ['Canvas low top', 'Leather court sneaker', 'Knit runner', 'High top canvas', 'Slip-on sneaker', 'Retro trainer', 'Chunky sole sneaker', 'Mesh walking shoe', 'Suede skate shoe'],
            'Sandals' => ['Leather slide', 'Sport sandal', 'Flip flop', 'Braided sandal', 'Platform sandal', 'Cork footbed sandal', 'Strappy sandal', 'Rubber pool slide'],
            'Boots' => ['Chelsea boot', 'Rain boot', 'Desert boot', 'Hiking boot', 'Work boot', 'Ankle zip boot', 'Lace-up combat boot', 'Suede chukka'],
        ],
    ],
    [
        'owner' => 'Sreymom Touch', 'store' => 'Angkor Silk', 'accent' => 'purple',
        'description' => 'Hand-woven silk scarves, shirts, and bags.',
        'address' => 'Pub Street, Siem Reap', 'hours' => 'Daily, 9:00 to 22:00',
        'price' => [900, 7500],
        'brands' => ['Golden Cocoon', 'Temple Loom', 'Silk Farm Angkor'],
        'categories' => [
            'Silk scarves' => ['Ikat silk scarf', 'Plain silk scarf', 'Striped silk shawl', 'Lotus print scarf', 'Silk krama', 'Gradient silk wrap', 'Checked silk scarf', 'Silk neck tie', 'Pocket square'],
            'Silk shirts' => ['Silk camp shirt', 'Silk blouse', 'Mandarin collar shirt', 'Silk tunic', 'Wrap top', 'Short sleeve silk shirt', 'Silk kimono jacket', 'Silk pajama set'],
            'Silk bags' => ['Silk clutch', 'Silk tote bag', 'Coin purse', 'Silk makeup pouch', 'Drawstring pouch', 'Silk laptop sleeve', 'Crossbody silk bag', 'Silk glasses case'],
        ],
    ],
    [
        'owner' => 'Visal Ouk', 'store' => 'Bayon Brew', 'accent' => 'slate',
        'description' => 'Cambodian coffee, roasted in small batches.',
        'address' => 'BKK1, Phnom Penh', 'hours' => 'Daily, 6:30 to 18:00',
        'price' => [350, 2200],
        'brands' => ['Ratanakiri Roast', 'Highland Beans', 'Bayon Roastery'],
        'categories' => [
            'Whole beans' => ['Mondulkiri robusta beans', 'Ratanakiri arabica beans', 'House blend beans', 'Espresso roast beans', 'Light roast beans', 'Honey process beans', 'Natural process beans', 'Decaf beans', 'Peaberry beans'],
            'Ground coffee' => ['Filter grind blend', 'Espresso grind', 'Phin filter grind', 'French press grind', 'Moka pot grind', 'Mild morning grind', 'Dark roast grind', 'Coconut coffee grind'],
            'Cold brew' => ['Cold brew bottle', 'Cold brew concentrate', 'Coconut cold brew', 'Oat milk cold brew', 'Cold brew kit', 'Vanilla cold brew', 'Sparkling cold brew', 'Cold brew bags'],
        ],
    ],
    [
        'owner' => 'Bopha Meas', 'store' => 'Tonle Skin Care', 'accent' => 'teal',
        'description' => 'Natural soaps, oils, and creams made in small batches.',
        'address' => 'Toul Tom Poung, Phnom Penh', 'hours' => 'Mon to Sat, 9:00 to 19:00',
        'price' => [300, 2800],
        'brands' => ['Rice Bran Lab', 'Coconut Grove', 'Tonle Botanicals'],
        'categories' => [
            'Face care' => ['Rice water toner', 'Aloe gel', 'Clay face mask', 'Vitamin C serum', 'Gentle face wash', 'Night cream', 'Sunscreen lotion', 'Lip balm', 'Eye cream'],
            'Body care' => ['Coconut body oil', 'Lemongrass soap', 'Tamarind body scrub', 'Shea body butter', 'Jasmine body lotion', 'Charcoal soap', 'Hand cream', 'Foot balm'],
            'Hair care' => ['Coconut shampoo', 'Kaffir lime conditioner', 'Hair oil', 'Scalp tonic', 'Solid shampoo bar', 'Leave-in cream', 'Hair mask', 'Wooden comb'],
        ],
    ],
    [
        'owner' => 'Piseth Nhem', 'store' => 'Sora Studio', 'accent' => 'blue',
        'description' => 'Notebooks, pens, and planners for work and school.',
        'address' => 'Russian Market, Phnom Penh', 'hours' => 'Mon to Sat, 9:00 to 18:00',
        'price' => [80, 1500],
        'brands' => ['Paper Lane', 'Ink Harbor', 'Sora Press'],
        'categories' => [
            'Notebooks' => ['Dot grid notebook', 'Lined notebook', 'Sketchbook', 'Pocket notebook', 'Kraft cover journal', 'Linen hardcover journal', 'Spiral notebook', 'Graph paper pad', 'Travel journal'],
            'Pens' => ['Fine liner set', 'Gel pen', 'Brush pen', 'Fountain pen', 'Highlighter set', 'Mechanical pencil', 'Ballpoint pen pack', 'Colored pencil tin'],
            'Planners' => ['Weekly planner', 'Daily planner', 'Undated planner', 'Monthly wall calendar', 'Desk calendar', 'Habit tracker pad', 'Budget planner', 'Study planner'],
        ],
    ],
    [
        'owner' => 'Kanha Seng', 'store' => 'Koh Rong Kids', 'accent' => 'orange',
        'description' => 'Wooden toys, puzzles, and soft toys for little ones.',
        'address' => 'Victory Hill, Sihanoukville', 'hours' => 'Daily, 9:00 to 19:00',
        'price' => [200, 3500],
        'brands' => ['Little Island', 'Tuk Tuk Toys', 'Seashell Play'],
        'categories' => [
            'Wooden toys' => ['Wooden tuk tuk', 'Stacking rings', 'Pull-along duck', 'Wooden train set', 'Shape sorter', 'Wooden blocks', 'Toy boat', 'Balance bird', 'Wooden xylophone'],
            'Puzzles' => ['Animal jigsaw', 'Map of Cambodia puzzle', 'Alphabet puzzle', 'Number puzzle', 'Ocean floor puzzle', 'Temple puzzle', 'Fruit puzzle', 'Shapes puzzle'],
            'Plush toys' => ['Plush elephant', 'Plush sea turtle', 'Plush monkey', 'Plush water buffalo', 'Plush dolphin', 'Plush crab', 'Plush owl', 'Plush dragon'],
        ],
    ],
];
