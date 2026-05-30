import Testing
@testable import PortfolioCore

@Test
func mockSummaryHasExpectedPortfolioName() async throws {
    #expect(MockPortfolioData.summary.portfolioName == "Main Portfolio")
    #expect(MockPortfolioData.holdings.data.isEmpty == false)
}
