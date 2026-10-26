<?php

namespace TusharKhan\DbErrorExplainer\Laravel;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Throwable;
use TusharKhan\DbErrorExplainer\Drivers\DriverResolver;
use TusharKhan\DbErrorExplainer\DTO\Diagnosis;
use TusharKhan\DbErrorExplainer\Explainer;

// Explains database exceptions using the Laravel connection they came from.
class DbExplainer
{
    /** @var array<string, Explainer> */
    private array $explainers = [];

    public function __construct(
        private DatabaseManager $db,
        private array $config,
        private ?LoggerInterface $logger = null,
    ) {}

    // Null when disabled, when it is not a database error, or when the connection's database is not supported.
    public function explain(Throwable $exception, ?string $connection = null): ?Diagnosis
    {
        if (!$this->config['enabled']) {
            return null;
        }

        $pdoException = $this->driverException($exception);
        if ($pdoException === null) {
            return null;
        }

        if ($connection === null && $exception instanceof QueryException) {
            $connection = $exception->getConnectionName();
        }

        return $this->explainer($connection)?->explain($pdoException);
    }

    public function canExplain(Throwable $exception): bool
    {
        return $this->config['enabled'] && $this->driverException($exception) !== null;
    }

    public function explainer(?string $connection = null): ?Explainer
    {
        $name = $connection ?? $this->db->getDefaultConnection();
        if (isset($this->explainers[$name])) {
            return $this->explainers[$name];
        }

        try {
            $conn = $this->db->connection($name);
        } catch (InvalidArgumentException) {
            return null;
        }

        $driver = $conn->getDriverName();
        if (!DriverResolver::supports($driver)) {
            return null;
        }

        // Only reuse a PDO that already exists; connecting here could hang on the error page
        $raw = $conn->getRawPdo();
        $pdo = $raw instanceof PDO ? $raw : null;

        $explainer = new Explainer($pdo, [
            'driver' => $driver,
            'allow_introspection' => $this->config['allow_introspection'],
            'redact_sensitive_data' => $this->config['redact_sensitive_data'],
            'custom_rules' => $this->config['custom_rules'],
        ], $this->logger);

        // Without a PDO there is no introspection, so try again next time
        if ($pdo !== null) {
            $this->explainers[$name] = $explainer;
        }

        return $explainer;
    }

    // The driver's own PDOException: its message has no SQL appended, which is what the rules expect.
    private function driverException(Throwable $exception): ?PDOException
    {
        $found = null;
        for ($e = $exception; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof PDOException) {
                $found = $e;
            }
        }

        return $found;
    }
}
