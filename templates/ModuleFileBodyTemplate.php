    /**
     * {@inheritdoc}
     *
     * @see \Eelly\Mvc\AbstractModule::registerUserAutoloaders()
     */
    public function registerUserAutoloaders(Di $di): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * @see \Eelly\Mvc\AbstractModule::registerUserServices()
     */
    public function registerUserServices(Di $di): void
    {
        /**
        // fastdfs service
        $di->setShared('fastdfs', function () {
            $config = $this->getModuleConfig()->fastdfs;

            return new FastDFSClient($config->toArray());
        });

        // 需配置config/dev/mysql.php
        $this->registerDbService();
        // 需配置config/dev/amqp.php
        $this->registerQueueService();
        **/
    }

    /**
     * {@inheritdoc}
     *
     * @see \Eelly\Mvc\AbstractModule::attachUserEvents()
     */
    public function attachUserEvents(Di $di): void
    {
        /**
        $eventsManager = $this->eventsManager;
        // 日志监听
        $eventsManager->attach('application', $di->get(ApiLoggerListener::class));
        $eventsManager->enablePriorities(true);
        // acl监听
        $eventsManager->attach('dispatch', $di->get(AclListener::class), 200);
        // 异步处理监听器
        $eventsManager->attach('dispatch', $di->get(AsyncAnnotationListener::class), 150);
        // 缓存监听
        $eventsManager->attach('dispatch', $di->get(CacheAnnotationListener::class), 100);
        // 参数校验
        $eventsManager->attach('dispatch', $di->get(ValidationAnnotationListener::class), 50);
        **/
    }

    /**
     * {@inheritdoc}
     *
     * @see \Eelly\Mvc\AbstractModule::registerCommands()
     */
    public function registerCommands(\Eelly\Console\Application $app): void
    {
        /**
        parent::registerCommands($app);
        $this->eventDispatcher->addListener(ConsoleEvents::COMMAND, function (ConsoleCommandEvent $event) {
            // ...
        });
        $this->eventDispatcher->addListener(ConsoleEvents::TERMINATE, function (ConsoleTerminateEvent $event) {
            // ...
        });
        $this->eventDispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) {
            // ...
        });
        $app->add($this->getDI()->getShared(TestCommand::class));
        **/
    }