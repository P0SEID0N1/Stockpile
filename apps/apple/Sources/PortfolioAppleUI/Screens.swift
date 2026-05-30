import Charts
import SwiftUI
import PortfolioCore

#if os(macOS)
public struct PortfolioDesktopRootView: View {
    @Bindable private var model: PortfolioAppModel

    public init(model: PortfolioAppModel) {
        self.model = model
    }

    public var body: some View {
        Group {
            if model.isAuthenticated {
                NavigationSplitView {
                    List(PortfolioAppModel.Section.allCases, selection: $model.selectedSection) { section in
                        Label(section.title, systemImage: section.symbolName)
                            .tag(section)
                    }
                    .navigationTitle("Stockpile")
                    .listStyle(.sidebar)
                } detail: {
                    detailView
                        .toolbar {
                            ToolbarItem(placement: .primaryAction) {
                                Button {
                                    Task { await model.refresh() }
                                } label: {
                                    Label("Refresh", systemImage: "arrow.clockwise")
                                }
                            }
                        }
                }
            } else {
                AuthenticationScreen(model: model)
                    .frame(minWidth: 520, minHeight: 560)
            }
        }
        .task {
            await model.bootstrap()
        }
    }

    @ViewBuilder
    private var detailView: some View {
        switch model.selectedSection {
        case .dashboard:
            DashboardScreen(model: model)
        case .portfolio:
            HoldingsScreen(model: model)
        case .journal:
            JournalScreen(model: model)
        case .plan:
            PlanScreen(model: model)
        case .settings:
            SettingsScreen(model: model)
        }
    }
}
#endif

public struct PortfolioMobileRootView: View {
    @Bindable private var model: PortfolioAppModel

    public init(model: PortfolioAppModel) {
        self.model = model
    }

    public var body: some View {
        Group {
            if model.isAuthenticated {
                TabView(selection: $model.selectedSection) {
                    NavigationStack { DashboardScreen(model: model) }
                        .tabItem { Label("Dashboard", systemImage: "chart.line.uptrend.xyaxis") }
                        .tag(PortfolioAppModel.Section.dashboard)

                    NavigationStack { HoldingsScreen(model: model) }
                        .tabItem { Label("Portfolio", systemImage: "briefcase") }
                        .tag(PortfolioAppModel.Section.portfolio)

                    NavigationStack { JournalScreen(model: model) }
                        .tabItem { Label("Journal", systemImage: "book.closed") }
                        .tag(PortfolioAppModel.Section.journal)

                    NavigationStack { PlanScreen(model: model) }
                        .tabItem { Label("Plan", systemImage: "scope") }
                        .tag(PortfolioAppModel.Section.plan)

                    NavigationStack { SettingsScreen(model: model) }
                        .tabItem { Label("Settings", systemImage: "gearshape") }
                        .tag(PortfolioAppModel.Section.settings)
                }
            } else {
                NavigationStack {
                    AuthenticationScreen(model: model)
                }
            }
        }
        .task {
            await model.bootstrap()
        }
    }
}

struct AuthenticationScreen: View {
    @Bindable var model: PortfolioAppModel

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 24) {
                VStack(alignment: .leading, spacing: 8) {
                    Text("Stockpile")
                        .font(.largeTitle.bold())
                    Text("Sign in to your self-hosted portfolio server.")
                        .foregroundStyle(.secondary)
                }

                VStack(alignment: .leading, spacing: 16) {
                    TextField("Server URL", text: $model.serverURL)
                        .textFieldStyle(.roundedBorder)
                        #if !os(macOS)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .keyboardType(.URL)
                        #endif

                    TextField("Email", text: $model.email)
                        .textFieldStyle(.roundedBorder)
                        #if !os(macOS)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .keyboardType(.emailAddress)
                        #endif

                    SecureField("Password", text: $model.password)
                        .textFieldStyle(.roundedBorder)

                    if let errorMessage = model.errorMessage {
                        Text(errorMessage)
                            .font(.footnote)
                            .foregroundStyle(.red)
                    }

                    Button {
                        model.saveServerURL()
                        Task { await model.signIn() }
                    } label: {
                        if model.authenticationState == .signingIn {
                            ProgressView()
                                .frame(maxWidth: .infinity)
                        } else {
                            Text("Sign In")
                                .frame(maxWidth: .infinity)
                        }
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(model.authenticationState == .signingIn || model.serverURL.isEmpty || model.email.isEmpty || model.password.isEmpty)
                }

                VStack(alignment: .leading, spacing: 8) {
                    Text("Requirements")
                        .font(.headline)
                    Text("Use your HTTPS production URL, for example `https://stockpile.maxwood.me`.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    Text("The first account must already exist on the server via `/setup`.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
            .padding(24)
            .frame(maxWidth: 520, alignment: .leading)
        }
        .navigationTitle("Login")
    }
}

struct DashboardScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                header
                metricGrid
                chartCard
                allocationCard
            }
            .padding()
        }
        .navigationTitle("Dashboard")
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(model.summary.portfolioName)
                .font(.largeTitle.bold())
            Text("Benchmark: \(model.summary.benchmarkSymbol)")
                .foregroundStyle(.secondary)
        }
    }

    private var metricGrid: some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 180), spacing: 16)], spacing: 16) {
            MetricCard(title: "Current Value", value: currency(model.summary.currentValue), tint: .blue)
            MetricCard(title: "Gain / Loss", value: currency(model.summary.totalGainLoss), tint: model.summary.totalGainLoss >= 0 ? .green : .red)
            MetricCard(title: "Day Change", value: "\(currency(model.summary.dayChange)) (\(model.summary.dayChangePercent.formatted(.number.precision(.fractionLength(2))))%)", tint: model.summary.dayChange >= 0 ? .green : .red)
            MetricCard(title: "Positions", value: "\(model.summary.holdingsCount)", tint: .orange)
        }
    }

    private var chartCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Portfolio Growth")
                .font(.headline)
            Chart {
                ForEach(model.performanceSeries.portfolio) { point in
                    LineMark(x: .value("Date", point.date), y: .value("Portfolio", point.value))
                        .foregroundStyle(.blue)
                }
                ForEach(model.performanceSeries.benchmark) { point in
                    LineMark(x: .value("Date", point.date), y: .value("Benchmark", point.value))
                        .foregroundStyle(.green)
                }
            }
            .frame(height: 240)
        }
        .cardStyle()
    }

    private var allocationCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Asset Allocation")
                .font(.headline)
            ForEach(model.summary.assetTypeAllocation) { slice in
                VStack(alignment: .leading, spacing: 6) {
                    HStack {
                        Text(slice.label.capitalized)
                        Spacer()
                        Text("\(slice.percentage.formatted(.number.precision(.fractionLength(1))))%")
                            .foregroundStyle(.secondary)
                    }
                    ProgressView(value: slice.percentage, total: 100)
                }
            }
        }
        .cardStyle()
    }
}

struct HoldingsScreen: View {
    @State private var isPresentingAddHolding = false
    let model: PortfolioAppModel

    var body: some View {
        List(model.holdings) { holding in
            VStack(alignment: .leading, spacing: 8) {
                HStack {
                    VStack(alignment: .leading, spacing: 2) {
                        Text(holding.asset.symbol ?? holding.asset.name)
                            .font(.headline)
                        Text(holding.asset.name)
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                    Text(currency(Double(holding.marketValue ?? holding.costBasisTotal) ?? 0))
                        .font(.headline)
                }
                HStack {
                    Label(holding.account.name, systemImage: "briefcase")
                    Spacer()
                    Text("Qty \(holding.quantity)")
                    Text("Basis \(currency(Double(holding.costBasisTotal) ?? 0))")
                }
                .font(.footnote)
                .foregroundStyle(.secondary)
            }
            .padding(.vertical, 4)
        }
        .navigationTitle("Portfolio")
        .toolbar {
            ToolbarItem(placement: .primaryAction) {
                Button {
                    isPresentingAddHolding = true
                } label: {
                    Label("Add Holding", systemImage: "plus")
                }
            }
        }
        .sheet(isPresented: $isPresentingAddHolding) {
            AddHoldingScreen(model: model)
        }
    }
}

struct AddHoldingScreen: View {
    @Environment(\.dismiss) private var dismiss
    @Bindable var model: PortfolioAppModel
    @State private var symbol = ""
    @State private var purchasePrice = ""
    @State private var quantity = ""
    @State private var isSaving = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Holding") {
                    TextField("Ticker symbol", text: $symbol)
                        #if !os(macOS)
                        .textInputAutocapitalization(.characters)
                        .autocorrectionDisabled()
                        #endif
                    TextField("Price paid per share", text: $purchasePrice)
                        #if !os(macOS)
                        .keyboardType(.decimalPad)
                        #endif
                    TextField("Number of shares", text: $quantity)
                        #if !os(macOS)
                        .keyboardType(.decimalPad)
                        #endif
                }

                Section("Quote source") {
                    Text("Live quotes come from the configured backend market-data provider, not directly from `GOOGLEFINANCE()`.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }

                if let errorMessage = model.addHoldingErrorMessage {
                    Section {
                        Text(errorMessage)
                            .font(.footnote)
                            .foregroundStyle(.red)
                    }
                }
            }
            .navigationTitle("Add Holding")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            await save()
                        }
                    }
                    .disabled(isSaving || symbol.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || purchasePrice.isEmpty || quantity.isEmpty)
                }
            }
        }
    }

    private func save() async {
        guard let parsedPurchasePrice = Double(purchasePrice),
              let parsedQuantity = Double(quantity) else {
            model.addHoldingErrorMessage = "Enter valid numeric values for price and shares."
            return
        }

        isSaving = true
        defer { isSaving = false }

        let success = await model.addHolding(
            symbol: symbol,
            purchasePrice: parsedPurchasePrice,
            quantity: parsedQuantity
        )

        if success {
            dismiss()
        }
    }
}

struct JournalScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        List(model.journalEntries) { entry in
            VStack(alignment: .leading, spacing: 6) {
                HStack {
                    Text(entry.entryType.capitalized)
                        .font(.headline)
                    Spacer()
                    Text(entry.tradeDate)
                        .foregroundStyle(.secondary)
                }
                Text(entry.holding?.asset.symbol ?? entry.account?.name ?? "General note")
                    .font(.subheadline)
                if let notes = entry.notes {
                    Text(notes)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
        .navigationTitle("Journal")
    }
}

struct PlanScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        List(model.planner.targets) { target in
            VStack(alignment: .leading, spacing: 8) {
                HStack {
                    Text(target.label.capitalized)
                        .font(.headline)
                    Spacer()
                    Text("\(target.targetPercentage.formatted(.number.precision(.fractionLength(1))))% target")
                        .foregroundStyle(.secondary)
                }
                HStack {
                    Text("Current \(target.currentPercentage.formatted(.number.precision(.fractionLength(1))))%")
                    Spacer()
                    Text(currency(target.deltaValue))
                        .foregroundStyle(target.deltaValue >= 0 ? .green : .red)
                }
                .font(.footnote)
            }
        }
        .navigationTitle("Plan")
    }
}

struct SettingsScreen: View {
    @Bindable var model: PortfolioAppModel

    var body: some View {
        Form {
            Section("Server") {
                TextField("Server URL", text: $model.serverURL)
                    #if !os(macOS)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                    .keyboardType(.URL)
                    #endif
                Button("Save Server URL") {
                    model.saveServerURL()
                }
                Text("Point this app at your self-hosted Laravel instance root URL.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Section("Account") {
                Text(model.currentUser?.email ?? model.email)
                if model.portfolios.isEmpty == false {
                    Picker("Portfolio", selection: Binding(
                        get: { model.selectedPortfolioID ?? model.portfolios.first?.id ?? 0 },
                        set: { newValue in
                            model.selectPortfolio(id: newValue)
                            Task { await model.refresh() }
                        }
                    )) {
                        ForEach(model.portfolios) { portfolio in
                            Text(portfolio.name).tag(portfolio.id)
                        }
                    }
                }

                Button("Sign Out", role: .destructive) {
                    Task { await model.signOut() }
                }
            }
            Section("Status") {
                if let errorMessage = model.errorMessage {
                    Text(errorMessage)
                        .foregroundStyle(.red)
                } else {
                    Text(model.isRefreshing ? "Refreshing..." : "Ready to sync")
                }
            }
        }
        .navigationTitle("Settings")
    }
}

struct MetricCard: View {
    let title: String
    let value: String
    let tint: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.title3.weight(.semibold))
                .foregroundStyle(tint)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
    }
}

private extension View {
    func cardStyle() -> some View {
        padding()
            .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
    }
}

private func currency(_ value: Double) -> String {
    value.formatted(.currency(code: "USD"))
}
