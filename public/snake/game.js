document.addEventListener('DOMContentLoaded', () => {
    // Game canvas setup
    const canvas = document.getElementById('game');
    const ctx = canvas.getContext('2d');
    const gridSize = 20;
    const tileCount = canvas.width / gridSize;
    
    // Game elements
    let snake = [];
    let food = {};
    let direction = 'right';
    let nextDirection = direction;
    let gameSpeed = 100; // milliseconds
    let gameLoop;
    let score = 0;
    let gameRunning = false;
    
    // DOM elements
    const startBtn = document.getElementById('start-btn');
    const restartBtn = document.getElementById('restart-btn');
    const scoreDisplay = document.getElementById('score');
    const upBtn = document.getElementById('up-btn');
    const downBtn = document.getElementById('down-btn');
    const leftBtn = document.getElementById('left-btn');
    const rightBtn = document.getElementById('right-btn');
    
    // Initialize game
    function initGame() {
        // Create initial snake
        snake = [
            {x: 5, y: 10},
            {x: 4, y: 10},
            {x: 3, y: 10}
        ];
        
        // Reset game state
        direction = 'right';
        nextDirection = direction;
        score = 0;
        scoreDisplay.textContent = score;
        
        // Generate first food
        generateFood();
        
        // Clear any existing game loop
        if (gameLoop) clearInterval(gameLoop);
    }
    
    // Start game
    function startGame() {
        if (gameRunning) return;
        
        gameRunning = true;
        startBtn.style.display = 'none';
        restartBtn.style.display = 'inline-block';
        
        // Start game loop
        gameLoop = setInterval(updateGame, gameSpeed);
    }
    
    // Update game state
    function updateGame() {
        // Update direction
        direction = nextDirection;
        
        // Move snake
        moveSnake();
        
        // Check collisions
        if (checkCollision()) {
            gameOver();
            return;
        }
        
        // Check food collision
        if (snake[0].x === food.x && snake[0].y === food.y) {
            // Don't remove tail to make snake grow
            score += 10;
            scoreDisplay.textContent = score;
            generateFood();
            
            // Increase speed slightly every 5 food items
            if (score % 50 === 0 && gameSpeed > 50) {
                gameSpeed -= 5;
                clearInterval(gameLoop);
                gameLoop = setInterval(updateGame, gameSpeed);
            }
        } else {
            // Remove tail segment if no food eaten
            snake.pop();
        }
        
        // Draw everything
        drawGame();
    }
    
    // Move snake based on direction
    function moveSnake() {
        // Calculate new head position
        const head = {x: snake[0].x, y: snake[0].y};
        
        switch(direction) {
            case 'up':
                head.y -= 1;
                break;
            case 'down':
                head.y += 1;
                break;
            case 'left':
                head.x -= 1;
                break;
            case 'right':
                head.x += 1;
                break;
        }
        
        // Add new head to beginning of snake array
        snake.unshift(head);
    }
    
    // Check for collisions with walls or self
    function checkCollision() {
        const head = snake[0];
        
        // Wall collision
        if (head.x < 0 || head.x >= tileCount || head.y < 0 || head.y >= tileCount) {
            return true;
        }
        
        // Self collision (check if head collides with any part of body)
        for (let i = 1; i < snake.length; i++) {
            if (head.x === snake[i].x && head.y === snake[i].y) {
                return true;
            }
        }
        
        return false;
    }
    
    // Generate food at random position
    function generateFood() {
        // Generate random position
        let newFood;
        let foodOnSnake;
        
        do {
            foodOnSnake = false;
            newFood = {
                x: Math.floor(Math.random() * tileCount),
                y: Math.floor(Math.random() * tileCount)
            };
            
            // Check if food is on snake
            for (let segment of snake) {
                if (segment.x === newFood.x && segment.y === newFood.y) {
                    foodOnSnake = true;
                    break;
                }
            }
        } while (foodOnSnake);
        
        food = newFood;
    }
    
    // Draw game elements
    function drawGame() {
        // Clear canvas
        ctx.fillStyle = '#222';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw snake
        ctx.fillStyle = '#4CAF50';
        for (let segment of snake) {
            ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize - 1, gridSize - 1);
        }
        
        // Draw snake head with different color
        ctx.fillStyle = '#2E7D32';
        ctx.fillRect(snake[0].x * gridSize, snake[0].y * gridSize, gridSize - 1, gridSize - 1);
        
        // Draw food
        ctx.fillStyle = '#FF5722';
        ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize - 1, gridSize - 1);
    }
    
    // Game over function
    function gameOver() {
        clearInterval(gameLoop);
        gameRunning = false;
        
        // Display game over message
        ctx.fillStyle = 'rgba(0, 0, 0, 0.75)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.font = '30px Arial';
        ctx.fillStyle = 'white';
        ctx.textAlign = 'center';
        ctx.fillText('Game Over!', canvas.width / 2, canvas.height / 2);
        
        ctx.font = '20px Arial';
        ctx.fillText(`Score: ${score}`, canvas.width / 2, canvas.height / 2 + 40);
    }
    
    // Event listeners
    startBtn.addEventListener('click', () => {
        initGame();
        startGame();
    });
    
    restartBtn.addEventListener('click', () => {
        initGame();
        startGame();
    });
    
    // Keyboard controls
    document.addEventListener('keydown', (e) => {
        switch(e.key) {
            case 'ArrowUp':
                if (direction !== 'down') nextDirection = 'up';
                break;
            case 'ArrowDown':
                if (direction !== 'up') nextDirection = 'down';
                break;
            case 'ArrowLeft':
                if (direction !== 'right') nextDirection = 'left';
                break;
            case 'ArrowRight':
                if (direction !== 'left') nextDirection = 'right';
                break;
        }
    });
    
    // Mobile controls
    upBtn.addEventListener('click', () => {
        if (direction !== 'down') nextDirection = 'up';
    });
    
    downBtn.addEventListener('click', () => {
        if (direction !== 'up') nextDirection = 'down';
    });
    
    leftBtn.addEventListener('click', () => {
        if (direction !== 'right') nextDirection = 'left';
    });
    
    rightBtn.addEventListener('click', () => {
        if (direction !== 'left') nextDirection = 'right';
    });
    
    // Initial setup
    initGame();
    drawGame();
});