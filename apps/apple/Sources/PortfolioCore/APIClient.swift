import Foundation

public struct PortfolioAPIClient: Sendable {
    public var baseURL: URL
    public var urlSession: URLSession
    public var tokenProvider: @Sendable () -> String?

    public init(
        baseURL: URL,
        urlSession: URLSession = .shared,
        tokenProvider: @escaping @Sendable () -> String? = { nil }
    ) {
        self.baseURL = baseURL
        self.urlSession = urlSession
        self.tokenProvider = tokenProvider
    }

    public func login(email: String, password: String, deviceName: String = "apple-client") async throws -> SessionPayload {
        try await send(
            path: "api/auth/login",
            method: "POST",
            body: [
                "email": email,
                "password": password,
                "device_name": deviceName,
            ]
        )
    }

    public func fetchCurrentSession() async throws -> CurrentSessionPayload {
        try await send(path: "api/me")
    }

    public func logout() async throws {
        _ = try await sendWithoutResponse(path: "api/auth/logout", method: "POST")
    }

    public func fetchSummary(portfolioID: Int? = nil) async throws -> PortfolioSummary {
        try await send(path: "api/performance/summary", queryItems: portfolioID.map { [URLQueryItem(name: "portfolio_id", value: String($0))] } ?? [])
    }

    public func fetchHoldings(portfolioID: Int? = nil) async throws -> HoldingListResponse {
        try await send(path: "api/holdings", queryItems: portfolioID.map { [URLQueryItem(name: "portfolio_id", value: String($0))] } ?? [])
    }

    public func fetchPerformanceSeries(portfolioID: Int? = nil, range: String = "1m") async throws -> PerformanceSeries {
        var queryItems = [URLQueryItem(name: "range", value: range)]
        if let portfolioID {
            queryItems.append(URLQueryItem(name: "portfolio_id", value: String(portfolioID)))
        }

        return try await send(path: "api/performance/timeseries", queryItems: queryItems)
    }

    public func fetchJournal(portfolioID: Int? = nil) async throws -> [JournalEntryDTO] {
        try await send(path: "api/journal", queryItems: portfolioID.map { [URLQueryItem(name: "portfolio_id", value: String($0))] } ?? [])
    }

    public func fetchPlanner(portfolioID: Int? = nil) async throws -> PlannerResponse {
        try await send(path: "api/plans/targets", queryItems: portfolioID.map { [URLQueryItem(name: "portfolio_id", value: String($0))] } ?? [])
    }

    public func createHolding(
        portfolioID: Int?,
        symbol: String,
        tradeDate: String,
        purchasePrice: Double?,
        quantity: Double,
        totalCost: Double
    ) async throws -> HoldingRow {
        var body: [String: String] = [
            "symbol": symbol.uppercased(),
            "name": symbol.uppercased(),
            "asset_type": "stocks",
            "trade_date": tradeDate,
            "quantity": String(quantity),
            "total_cost": String(totalCost),
        ]

        if let purchasePrice {
            body["purchase_price"] = String(purchasePrice)
        }

        if let portfolioID {
            body["portfolio_id"] = String(portfolioID)
        }

        return try await send(
            path: "api/holdings",
            method: "POST",
            body: body
        )
    }

    public func resetPortfolio(id: Int) async throws -> ResetPortfolioResponse {
        try await send(
            path: "api/portfolios/\(id)/reset",
            method: "POST"
        )
    }

    private func send<Response: Decodable>(
        path: String,
        method: String = "GET",
        queryItems: [URLQueryItem] = [],
        body: [String: String]? = nil
    ) async throws -> Response {
        var components = URLComponents(url: baseURL.appending(path: path), resolvingAgainstBaseURL: false)
        components?.queryItems = queryItems.isEmpty ? nil : queryItems

        guard let url = components?.url else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        if let token = tokenProvider() {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        if let body {
            request.httpBody = try JSONSerialization.data(withJSONObject: body)
            request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        }

        let (data, response) = try await urlSession.data(for: request)
        guard let httpResponse = response as? HTTPURLResponse else {
            throw APIError.invalidResponse
        }

        guard (200..<300).contains(httpResponse.statusCode) else {
            throw APIError.http(httpResponse.statusCode, String(data: data, encoding: .utf8) ?? "Unknown error")
        }

        let decoder = JSONDecoder()
        decoder.dateDecodingStrategy = .iso8601
        return try decoder.decode(Response.self, from: data)
    }

    private func sendWithoutResponse(
        path: String,
        method: String = "POST",
        queryItems: [URLQueryItem] = [],
        body: [String: String]? = nil
    ) async throws -> VoidResponse {
        try await send(path: path, method: method, queryItems: queryItems, body: body)
    }
}

public enum APIError: LocalizedError {
    case invalidURL
    case invalidResponse
    case http(Int, String)

    public var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "The configured server URL is invalid."
        case .invalidResponse:
            return "The server returned an unexpected response."
        case let .http(status, message):
            return "HTTP \(status): \(message)"
        }
    }
}

private struct VoidResponse: Decodable {}
