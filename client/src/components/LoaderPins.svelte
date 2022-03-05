<div class="bottom-1 container relative grid w-80 items-center h-20">
  <div
    class="square right-28 md:-left-52 animate-rotate grid transition duration-300 absolute w-full md:w-screen"
  >
    <i class="text-6xl fab fa-pinterest text-primary" />
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
