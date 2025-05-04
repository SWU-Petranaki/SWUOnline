
<div class="header-wrapper">
    <div class="home-header">
        <div class="title">
            <a href="./MainMenu.php" class="title title-container bg-yellow">
                <h1 class="home-title" style="white-space: nowrap;">Petrana<span>k</span>i</h1>
                <p>
                Fan-Made, Open-Source
                <br/>Star Wars Unlimited Simulator
                </p>
            </a>
        </div>
    </div>
</div>

<style>
/* Responsive styles for Header */
@media screen and (max-width: 768px) {
    .home-header {
        height: auto !important;
        padding: 5px 0 !important;
        position: relative;
        pointer-events: auto !important;
        z-index: 30;
    }
    
    .title-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px !important;
    }
    
    .home-title {
        font-size: 1.8rem !important;
        margin-bottom: 5px;
    }
    
    .title p {
        font-size: 0.8rem;
        text-align: center;
        line-height: 1.2;
    }
}
</style>