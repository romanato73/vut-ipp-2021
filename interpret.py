import sys
from src.Interpret.App import App
from src.Support.ErrorHandler import ErrorHandler

# DEBUG:
#  tests/both/write

handler = ErrorHandler()

# Create Application instance
app = App()

# Register arguments
app.registerArguments([
    "--source=file",
    "--input=file"
])

# Listen for arguments
app.listen(sys.argv)

# Run interpret
app.runInterpret()

# Terminate app
app.terminate()
