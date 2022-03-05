<div
    class="loader w-screen h-screen fixed bg-white top-0 left-0 grid justify-items-center"
>
    <div class="container grid absolute w-screen h-screen items-center">
        <div
            class="square -left-28 animate-rotate grid items-center transition duration-300 absolute w-full"
        >
            <i class="text-6xl fab fa-pinterest text-primary" />
        </div>
    </div>
</div>

<style>
    .container {
        -webkit-transform-style: preserve-3d;
        transform-style: preserve-3d;
    }

    .square {
        display: -webkit-box;
        display: flex;
        justify-content: center;
    }

    @keyframes rotate {
        from {
            -webkit-transform: translateX(120px) rotateY(0deg);
            transform: translateX(120px) rotateY(0deg);
        }
        to {
            -webkit-transform: translateX(120px) rotateY(360deg);
            transform: translateX(120px) rotateY(360deg);
        }
    }
</style>
